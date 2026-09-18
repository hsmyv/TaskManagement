<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Http\Resources\TaskResource;
use App\Models\Space;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(private readonly TaskService $taskService) {}

    public function index(Request $request, Space $space): JsonResponse
    {
        $this->authorize('view', $space);

        $query = Task::query()
            ->where('space_id', $space->id)
            ->whereNull('parent_task_id')
            ->with(['assignees', 'helpers', 'supervisors', 'creator', 'assigner'])
            ->withCount([
                'subtasks',
                'attachments',
                'allComments as comments_count',
                'subtasks as completed_subtasks_count' => fn ($query) => $query->where('status', 'completed'),
            ]);

        if (!$request->filled('board_id')) {
            $employee = $request->user();
            $query->where(function ($query) use ($employee) {
                $query->where('created_by', $employee->id)
                    ->orWhere('assigned_by', $employee->id)
                    ->orWhereHas('assignees', fn ($assignees) => $assignees->where('employees.id', $employee->id))
                    ->orWhereHas('helpers', fn ($helpers) => $helpers->where('employees.id', $employee->id))
                    ->orWhereHas('supervisors', fn ($supervisors) => $supervisors->where('employees.id', $employee->id))
                    ->orWhereHas('subtasks', function ($subtasks) use ($employee) {
                        $subtasks->where('created_by', $employee->id)
                            ->orWhere('assigned_by', $employee->id)
                            ->orWhereHas('assignees', fn ($assignees) => $assignees->where('employees.id', $employee->id))
                            ->orWhereHas('helpers', fn ($helpers) => $helpers->where('employees.id', $employee->id))
                            ->orWhereHas('supervisors', fn ($supervisors) => $supervisors->where('employees.id', $employee->id));
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('assignee_id')) {
            $assigneeId = $request->integer('assignee_id');
            $query->where(function ($query) use ($assigneeId) {
                $query->where(function ($query) use ($assigneeId) {
                    $query->where('created_by', $assigneeId)
                        ->where(function ($query) use ($assigneeId) {
                            $query->whereNull('assigned_by')
                                ->orWhere('assigned_by', $assigneeId);
                        });
                })
                    ->orWhere('assigned_by', $assigneeId)
                    ->orWhereHas('assignees', fn($q) => $q->where('employees.id', $assigneeId))
                    ->orWhereHas('helpers', fn($q) => $q->where('employees.id', $assigneeId))
                    ->orWhereHas('supervisors', fn($q) => $q->where('employees.id', $assigneeId))
                    ->orWhereHas('subtasks', fn($q) => $q->where('assigned_by', $assigneeId))
                    ->orWhereHas('subtasks.assignees', fn($q) => $q->where('employees.id', $assigneeId))
                    ->orWhereHas('subtasks.helpers', fn($q) => $q->where('employees.id', $assigneeId))
                    ->orWhereHas('subtasks.supervisors', fn($q) => $q->where('employees.id', $assigneeId));
            });
        }
        if ($request->filled('created_by')) {
            $creatorId = $request->integer('created_by');
            $query->where(function ($query) use ($creatorId) {
                $query->where('created_by', $creatorId)
                    ->orWhere('assigned_by', $creatorId);
            });
        }
        if ($request->filled('board_id')) {
            $query->where('board_id', $request->board_id);
        }
        if ($request->boolean('unassigned_board')) {
            $query->whereNull('board_id');
        }
        if ($request->filled('due_date_from')) {
            $query->where('due_date', '>=', $request->due_date_from);
        }
        if ($request->filled('due_date_to')) {
            $query->where('due_date', '<=', $request->due_date_to);
        }
        if ($request->boolean('due_soon')) {
            $days = $request->integer('due_days', 7);
            $query->dueSoon($days);
        }
        if ($request->boolean('overdue')) {
            $query->overdue();
        }
        if ($request->filled('q')) {
            $query->where('title', 'like', "%{$request->q}%");
        }

        $tasks = $request->filled('board_id')
            ? $query->orderBy('board_position')->get()
            : $query->latest()->get();

        if ($request->boolean('grouped')) {
            $grouped = $tasks->groupBy('status')->map(fn($g) => TaskResource::collection($g));
            return response()->json($grouped);
        }

        return response()->json(TaskResource::collection($tasks));
    }

    public function export(Request $request, Space $space): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('view', $space);

        $query = Task::query()
            ->where('space_id', $space->id)
            ->whereNull('parent_task_id')
            ->with(['board', 'assignees', 'helpers', 'supervisors', 'creator', 'assigner', 'subtasks', 'checklists'])
            ->withCount([
                'subtasks',
                'attachments',
                'allComments as comments_count',
                'subtasks as completed_subtasks_count' => fn ($query) => $query->where('status', 'completed'),
            ])
            ->forEmployee($request->user());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('assignee_id')) {
            $assigneeId = $request->integer('assignee_id');
            $query->where(function ($query) use ($assigneeId) {
                $query->where(function ($query) use ($assigneeId) {
                    $query->where('created_by', $assigneeId)
                        ->where(function ($query) use ($assigneeId) {
                            $query->whereNull('assigned_by')
                                ->orWhere('assigned_by', $assigneeId);
                        });
                })
                    ->orWhere('assigned_by', $assigneeId)
                    ->orWhereHas('assignees', fn($q) => $q->where('employees.id', $assigneeId))
                    ->orWhereHas('helpers', fn($q) => $q->where('employees.id', $assigneeId))
                    ->orWhereHas('supervisors', fn($q) => $q->where('employees.id', $assigneeId))
                    ->orWhereHas('subtasks', fn($q) => $q->where('assigned_by', $assigneeId))
                    ->orWhereHas('subtasks.assignees', fn($q) => $q->where('employees.id', $assigneeId))
                    ->orWhereHas('subtasks.helpers', fn($q) => $q->where('employees.id', $assigneeId))
                    ->orWhereHas('subtasks.supervisors', fn($q) => $q->where('employees.id', $assigneeId));
            });
        }
        if ($request->filled('created_by')) {
            $query->where('created_by', $request->created_by);
        }
        if ($request->filled('board_id')) {
            $query->where('board_id', $request->board_id);
        }
        if ($request->boolean('unassigned_board')) {
            $query->whereNull('board_id');
        }
        if ($request->filled('due_date_from')) {
            $query->where('due_date', '>=', $request->due_date_from);
        }
        if ($request->filled('due_date_to')) {
            $query->where('due_date', '<=', $request->due_date_to);
        }
        if ($request->boolean('due_soon')) {
            $query->dueSoon($request->integer('due_days', 7));
        }
        if ($request->boolean('overdue')) {
            $query->overdue();
        }
        if ($request->filled('q')) {
            $query->where('title', 'like', "%{$request->q}%");
        }

        $tasks = $query->orderBy('board_position')->latest()->get();
        $filename = 'tasks-' . now()->format('Y-m-d-His') . '.xls';

        return response()->streamDownload(function () use ($tasks) {
            echo '<html><head><meta charset="UTF-8"></head><body><table border="1">';
            echo '<tr>';
            foreach (['Tapşırıq', 'Alt tapşırıqlar', 'Layihə', 'Status', 'Prioritet', 'Məsul şəxslər', 'Köməkçilər', 'Nəzarətçilər', 'Təyin edən', 'Yaradan', 'Başlama tarixi', 'Son tarix', 'İrəliləyiş', 'Yoxlama siyahısı', 'Gecikib'] as $heading) {
                echo '<th>' . e($heading) . '</th>';
            }
            echo '</tr>';

            foreach ($tasks as $task) {
                $checklist = $task->checklist_progress;
                $assignees = $task->assignees->pluck('full_name')->implode(', ');
                $helpers = $task->helpers->pluck('full_name')->implode(', ');
                $supervisors = $task->supervisors->pluck('full_name')->implode(', ');
                $row = [
                    $task->title,
                    $task->subtasks->pluck('title')->implode(', '),
                    $task->board?->name,
                    \App\Models\StatusHistory::statusLabel($task->status),
                    match ($task->priority) {
                        Task::PRIORITY_LOW => 'Aşağı',
                        Task::PRIORITY_MEDIUM => 'Orta',
                        Task::PRIORITY_HIGH => 'Yüksək',
                        Task::PRIORITY_URGENT => 'Təcili',
                        default => $task->priority,
                    },
                    $assignees,
                    $helpers,
                    $supervisors,
                    $task->assigner?->full_name,
                    $task->creator?->full_name,
                    $task->start_date?->format('d.m.Y'),
                    $task->due_date?->format('d.m.Y'),
                    $task->progress_percentage . '%',
                    ($checklist['done'] ?? 0) . '/' . ($checklist['total'] ?? 0),
                    $task->isOverdue() ? 'Bəli' : 'Xeyr',
                ];

                echo '<tr>';
                foreach ($row as $cell) {
                    echo '<td>' . e((string) $cell) . '</td>';
                }
                echo '</tr>';
            }

            echo '</table></body></html>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    public function calendar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'space_id' => 'nullable|integer|exists:spaces,id',
            'assignee_id' => 'nullable|integer|exists:employees,id',
        ]);

        $query = Task::query()
            ->whereNull('parent_task_id')
            ->whereNotNull('due_date')
            ->with(['space', 'board', 'creator', 'assigner', 'assignees', 'helpers', 'supervisors'])
            ->forEmployee($request->user());

        if (!empty($data['from'])) {
            $query->where('due_date', '>=', $data['from']);
        }

        if (!empty($data['to'])) {
            $query->where('due_date', '<=', $data['to']);
        }

        if (!empty($data['space_id'])) {
            $query->where('space_id', $data['space_id']);
        }

        if (!empty($data['assignee_id'])) {
            $employeeId = (int) $data['assignee_id'];
            $query->where(function ($query) use ($employeeId) {
                $query->where('created_by', $employeeId)
                    ->orWhere('assigned_by', $employeeId)
                    ->orWhereHas('assignees', fn ($assignees) => $assignees->where('employees.id', $employeeId))
                    ->orWhereHas('helpers', fn ($helpers) => $helpers->where('employees.id', $employeeId))
                    ->orWhereHas('supervisors', fn ($supervisors) => $supervisors->where('employees.id', $employeeId));
            });
        }

        $tasks = $query
            ->orderBy('due_date')
            ->orderBy('priority')
            ->get();

        return response()->json(TaskResource::collection($tasks));
    }

    public function store(Request $request, Space $space): JsonResponse
    {
        $this->authorize('create', [Task::class, $space]);

        $data = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'priority'         => 'nullable|in:low,medium,high,urgent',
            'start_date'       => 'nullable|date',
            'due_date'         => 'nullable|date|after_or_equal:start_date',
            'estimated_hours'  => 'nullable|integer|min:1',
            'visibility'       => 'nullable|in:all_members,managers_only',
            'require_approval' => 'nullable|boolean',
            'deadline_locked'  => 'nullable|boolean',
            'assignee_ids'     => 'nullable|array',
            'assignee_ids.*'   => 'exists:employees,id',
            'helper_ids'       => 'nullable|array',
            'helper_ids.*'     => 'exists:employees,id',
            'supervisor_ids'   => 'nullable|array',
            'supervisor_ids.*' => 'exists:employees,id',
            'assigned_by_id'   => 'nullable|exists:employees,id',
            'checklists'       => 'nullable|array',
            'checklists.*.title' => 'required|string|max:255',
        ]);

        $task = $this->taskService->createTask($space, $data, $request->user());

        return response()->json(new TaskResource($task->load(['assignees', 'helpers', 'supervisors', 'creator', 'space'])), 201);
    }

    public function show(Request $request, Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $task->load([
            'creator', 'assigner', 'assignees', 'helpers', 'supervisors', 'space',
            'subtasks.creator', 'subtasks.assignees', 'subtasks.helpers', 'subtasks.supervisors', 'checklists.completedBy',
            'attachments.uploader', 'comments.author', 'comments.replies.author',
            'statusHistory.changedBy', 'activityLogs.employee',
        ])->loadCount([
            'subtasks',
            'attachments',
            'allComments as comments_count',
            'subtasks as completed_subtasks_count' => fn ($query) => $query->where('status', 'completed'),
        ]);

        return response()->json(new TaskResource($task));
    }

    public function activity(Request $request, Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $logs = $task->activityLogs()
            ->with('employee')
            ->limit($request->integer('limit', 50))
            ->get();

        return response()->json(ActivityLogResource::collection($logs));
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $data = $request->validate([
            'title'            => 'sometimes|string|max:255',
            'description'      => 'nullable|string',
            'priority'         => 'nullable|in:low,medium,high,urgent',
            'start_date'       => 'nullable|date',
            'due_date'         => 'nullable|date',
            'estimated_hours'  => 'nullable|integer|min:1',
            'visibility'       => 'nullable|in:all_members,managers_only',
            'require_approval' => 'sometimes|boolean',
            'deadline_locked'  => 'sometimes|boolean',
            'assignee_ids'     => 'nullable|array',
            'assignee_ids.*'   => 'exists:employees,id',
            'helper_ids'       => 'nullable|array',
            'helper_ids.*'     => 'exists:employees,id',
            'supervisor_ids'   => 'nullable|array',
            'supervisor_ids.*' => 'exists:employees,id',
        ]);

        $task = $this->taskService->updateTask($task, $data, $request->user());

        return response()->json(new TaskResource($task));
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        $this->authorize('delete', $task);
        $this->taskService->deleteTask($task, $request->user());
        return response()->json(['message' => 'TapÅŸÄ±rÄ±q silindi.']);
    }


    /**
     * Status dÉ™yiÅŸikliyi
     */
    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        $this->authorize('changeStatus', $task);

        $data = $request->validate([
            'status'  => 'required|in:todo,in_progress,waiting_for_approve,completed,canceled',
            'comment' => 'nullable|string|max:1000',
        ]);

        $task = $this->taskService->changeStatus($task, $data['status'], $request->user(), $data['comment'] ?? null);

        return response()->json(new TaskResource($task));
    }

    /**
     * TapÅŸÄ±rÄ±ÄŸÄ± tÉ™sdiqlÉ™ (Waiting â†’ Completed)
     */
    public function approve(Request $request, Task $task): JsonResponse
    {
        $this->authorize('approve', $task);

        if ($task->status !== 'waiting_for_approve') {
            return response()->json(['message' => 'Bu tapÅŸÄ±rÄ±q tÉ™sdiq gÃ¶zlÉ™mir.'], 422);
        }

        $task = $this->taskService->approveTask($task, $request->user());

        return response()->json(new TaskResource($task));
    }

    /**
     * Assignee-lÉ™ri yenilÉ™
     */
    public function updateAssignees(Request $request, Task $task): JsonResponse
    {
        $this->authorize('assign', $task);

        $data = $request->validate([
            'assignee_ids'   => 'required|array',
            'assignee_ids.*' => 'exists:employees,id',
        ]);

        $this->taskService->syncAssignees($task, $data['assignee_ids'], $request->user());

        return response()->json(new TaskResource($task->load('assignees')));
    }

    public function updateCollaborators(Request $request, Task $task): JsonResponse
    {
        $this->authorize('assign', $task);

        $data = $request->validate([
            'assignee_ids' => 'nullable|array',
            'assignee_ids.*' => 'exists:employees,id',
            'helper_ids' => 'nullable|array',
            'helper_ids.*' => 'exists:employees,id',
            'supervisor_ids' => 'nullable|array',
            'supervisor_ids.*' => 'exists:employees,id',
        ]);

        if (array_key_exists('assignee_ids', $data)) {
            $this->taskService->syncAssignees($task, $data['assignee_ids'] ?? [], $request->user());
        }

        if (array_key_exists('helper_ids', $data)) {
            $this->taskService->syncHelpers($task, $data['helper_ids'] ?? [], $request->user());
        }

        if (array_key_exists('supervisor_ids', $data)) {
            $this->taskService->syncSupervisors($task, $data['supervisor_ids'] ?? [], $request->user());
        }

        return response()->json(new TaskResource($task->load(['assigner', 'assignees', 'helpers', 'supervisors'])));
    }

    /**
     * Drag & Drop: status dÉ™yiÅŸikliyi
     */
    public function updateOrder(Request $request, Task $task): JsonResponse
    {
        $this->authorize('changeStatus', $task);

        $data = $request->validate([
            'status' => 'required|in:todo,in_progress,waiting_for_approve,completed,canceled',
        ]);

        $task = $this->taskService->updateOrder($task, $data['status'], $request->user());

        return response()->json(new TaskResource($task));
    }

    /**
     * Alt tapÅŸÄ±rÄ±qlar
     */
    public function subtasks(Request $request, Task $task): JsonResponse
    {
        $subtasks = $task->subtasks()
            ->with(['assignees', 'creator'])
            ->withCount(['attachments', 'allComments as comments_count'])
            ->get();

        return response()->json(TaskResource::collection($subtasks));
    }

    public function storeSubtask(Request $request, Task $task): JsonResponse
    {
        $this->authorize('create', [Task::class, $task->space]);

        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'priority'        => 'nullable|in:low,medium,high,urgent',
            'due_date'        => 'nullable|date',
            'estimated_hours' => 'nullable|integer|min:1',
            'assignee_ids'    => 'nullable|array',
            'assignee_ids.*'  => 'exists:employees,id',
            'assigned_by_id'  => 'nullable|exists:employees,id',
        ]);

        $data['parent_task_id'] = $task->id;

        $subtask = $this->taskService->createTask($task->space, $data, $request->user());

        return response()->json(new TaskResource($subtask->load(['assignees', 'creator'])), 201);
    }
}
