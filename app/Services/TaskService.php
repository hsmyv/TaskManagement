<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Employee;
use App\Models\Space;
use App\Models\StatusHistory;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class TaskService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function createTask(Space $space, array $data, Employee $creator): Task
    {
        return DB::transaction(function () use ($space, $data, $creator) {
            $assignedBy = $data['assigned_by_id'] ?? $creator->id;

            $task = Task::create([
                'title'            => $data['title'],
                'description'      => $data['description'] ?? null,
                'space_id'         => $space->id,
                'parent_task_id'   => $data['parent_task_id'] ?? null,
                'status'           => TaskStatus::Todo->value,
                'priority'         => $data['priority'] ?? 'medium',
                'start_date'       => $data['start_date'] ?? null,
                'due_date'         => $data['due_date'] ?? null,
                'estimated_hours'  => $data['estimated_hours'] ?? null,
                'visibility'       => $data['visibility'] ?? 'all_members',
                'require_approval' => $data['require_approval'] ?? false,
                'deadline_locked'  => $data['deadline_locked'] ?? false,
                'created_by'       => $creator->id,
                'assigned_by'      => $assignedBy,
            ]);

            if (!empty($data['assignee_ids'])) {
                $this->syncAssignees($task, $data['assignee_ids'], $creator);
            }

            if (!empty($data['checklists'])) {
                foreach ($data['checklists'] as $i => $item) {
                    $task->checklists()->create(['title' => $item['title'], 'order' => $i]);
                }
            }

            StatusHistory::create([
                'task_id'     => $task->id,
                'from_status' => null,
                'to_status'   => TaskStatus::Todo->value,
                'changed_by'  => $creator->id,
                'changed_at'  => now(),
            ]);

            $task->load(['creator', 'assigner', 'assignees', 'space']);
            $this->notificationService->notifyTaskCreated($task, $creator);

            return $task;
        });
    }

    public function updateTask(Task $task, array $data, Employee $updater): Task
    {
        return DB::transaction(function () use ($task, $data, $updater) {
            if (isset($data['due_date']) && $task->deadline_locked) {
                abort_unless(
                    $task->assigned_by === $updater->id || $updater->hasGlobalAccess(),
                    403,
                    'Deadline kilidlidir. Yalnız assign edən dəyişdirə bilər.'
                );
            }

            $changes  = [];
            $fillable = [
                'title', 'description', 'priority', 'start_date',
                'due_date', 'estimated_hours', 'visibility',
                'require_approval', 'deadline_locked',
            ];

            foreach ($fillable as $field) {
                if (array_key_exists($field, $data) && $task->$field !== $data[$field]) {
                    $changes[$field] = ['from' => $task->$field, 'to' => $data[$field]];
                    $task->$field    = $data[$field];
                }
            }

            $task->save();

            if (isset($data['assignee_ids'])) {
                $this->syncAssignees($task, $data['assignee_ids'], $updater);
            }

            $task->load(['creator', 'assigner', 'assignees', 'space']);

            if (!empty($changes)) {
                $this->notificationService->notifyTaskUpdated($task, $updater, $changes);
            }

            return $task;
        });
    }

    public function changeStatus(Task $task, string $newStatus, Employee $changer, ?string $comment = null): Task
    {
        return DB::transaction(function () use ($task, $newStatus, $changer, $comment) {
            $currentStatus  = TaskStatus::from($task->status);
            $resolvedStatus = TaskStatus::resolveNextStatus($newStatus, $task->require_approval);

            if (!$currentStatus->canTransitionTo($resolvedStatus)) {
                abort(422, "'{$currentStatus->label()}' → '{$resolvedStatus->label()}' keçidi mümkün deyil.");
            }

            $oldStatus = $task->status;
            $task->update(['status' => $resolvedStatus->value]);

            StatusHistory::create([
                'task_id'     => $task->id,
                'from_status' => $oldStatus,
                'to_status'   => $resolvedStatus->value,
                'changed_by'  => $changer->id,
                'comment'     => $comment,
                'changed_at'  => now(),
            ]);

            $task->load(['creator', 'assigner', 'assignees', 'space']);
            $this->notificationService->notifyStatusChanged($task, $changer, $oldStatus, $resolvedStatus->value);

            return $task;
        });
    }

    public function approveTask(Task $task, Employee $approver): Task
    {
        return DB::transaction(function () use ($task, $approver) {
            $oldStatus = $task->status;
            $task->update(['status' => TaskStatus::Completed->value]);

            StatusHistory::create([
                'task_id'     => $task->id,
                'from_status' => $oldStatus,
                'to_status'   => TaskStatus::Completed->value,
                'changed_by'  => $approver->id,
                'comment'     => 'Tapşırıq təsdiqləndi.',
                'changed_at'  => now(),
            ]);

            $task->load(['creator', 'assigner', 'assignees', 'space']);
            $this->notificationService->notifyTaskApproved($task, $approver);

            return $task;
        });
    }

    public function syncAssignees(Task $task, array $employeeIds, Employee $assigner): void
    {
        $syncData = [];
        foreach ($employeeIds as $employeeId) {
            $syncData[$employeeId] = [
                'assigned_by' => $assigner->id,
                'assigned_at' => now(),
            ];
        }
        $task->assignees()->sync($syncData);
        $this->notificationService->notifyAssigneesChanged($task, $assigner);
    }

    public function updateOrder(Task $task, string $newStatus, Employee $mover): Task
    {
        if ($task->status !== $newStatus) {
            $task = $this->changeStatus($task, $newStatus, $mover);
        }
        return $task;
    }

    public function deleteTask(Task $task): void
    {
        $task->delete();
    }
}
