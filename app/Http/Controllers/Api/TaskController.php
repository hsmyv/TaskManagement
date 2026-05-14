<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Space;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(private readonly TaskService $taskService) {}

    /**
     * Space-ə aid bütün tasklar (Kanban üçün)
     */
    public function index(Request $request, Space $space): JsonResponse
    {
        $this->authorize('view', $space);

        $query = Task::query()
            ->where('space_id', $space->id)
            ->whereNull('parent_task_id')
            ->with(['assignees', 'creator', 'assigner'])
            ->withCount(['subtasks', 'attachments', 'comments'])
            ->forEmployee($request->user());

        // Filterlər (TIS section 5.2)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('assignee_id')) {
            $query->whereHas('assignees', fn($q) => $q->where('employees.id', $request->assignee_id));
        }
        if ($request->filled('created_by')) {
            $query->where('created_by', $request->created_by);
        }
        if ($request->filled('board_id')) {
            $query->where('board_id', $request->board_id);
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

        // Kanban üçün statuslara görə qruplaşdır
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
            ->with(['board', 'assignees', 'creator', 'assigner', 'subtasks', 'checklists'])
            ->withCount(['subtasks', 'attachments', 'comments'])
            ->forEmployee($request->user());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('assignee_id')) {
            $query->whereHas('assignees', fn($q) => $q->where('employees.id', $request->assignee_id));
        }
        if ($request->filled('created_by')) {
            $query->where('created_by', $request->created_by);
        }
        if ($request->filled('board_id')) {
            $query->where('board_id', $request->board_id);
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
            foreach (['Ad', 'Layihə', 'Status', 'Prioritet', 'Məsul şəxslər', 'Təyin edən', 'Yaradan', 'Başlama tarixi', 'Son tarix', 'İrəliləyiş', 'Alt tapşırıqlar', 'Yoxlama siyahısı', 'Gecikib'] as $heading) {
                echo '<th>' . e($heading) . '</th>';
            }
            echo '</tr>';

            foreach ($tasks as $task) {
                $checklist = $task->checklist_progress;
                $assignees = $task->assignees->pluck('full_name')->implode(', ');
                $row = [
                    $task->title,
                    $task->board?->name,
                    \App\Models\StatusHistory::statusLabel($task->status),
                    $task->priority,
                    $assignees,
                    $task->assigner?->full_name,
                    $task->creator?->full_name,
                    $task->start_date?->format('d.m.Y'),
                    $task->due_date?->format('d.m.Y'),
                    $task->progress_percentage . '%',
                    $task->subtasks->where('status', Task::STATUS_COMPLETED)->count() . '/' . $task->subtasks->count(),
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
            'assigned_by_id'   => 'nullable|exists:employees,id',
            'checklists'       => 'nullable|array',
            'checklists.*.title' => 'required|string|max:255',
        ]);

        $task = $this->taskService->createTask($space, $data, $request->user());

        return response()->json(new TaskResource($task->load(['assignees', 'creator', 'space'])), 201);
    }

    public function show(Request $request, Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $task->load([
            'creator', 'assigner', 'assignees', 'space',
            'subtasks.creator', 'subtasks.assignees', 'checklists.completedBy',
            'attachments.uploader', 'comments.author', 'comments.replies.author',
            'statusHistory.changedBy',
        ])->loadCount(['subtasks', 'attachments', 'comments']);

        return response()->json(new TaskResource($task));
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
        ]);

        $task = $this->taskService->updateTask($task, $data, $request->user());

        return response()->json(new TaskResource($task));
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        $this->authorize('delete', $task);
        $this->taskService->deleteTask($task, $request->user());
        return response()->json(['message' => 'Tapşırıq silindi.']);
    }
    

    /**
     * Status dəyişikliyi
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
     * Tapşırığı təsdiqlə (Waiting → Completed)
     */
    public function approve(Request $request, Task $task): JsonResponse
    {
        $this->authorize('approve', $task);

        if ($task->status !== 'waiting_for_approve') {
            return response()->json(['message' => 'Bu tapşırıq təsdiq gözləmir.'], 422);
        }

        $task = $this->taskService->approveTask($task, $request->user());

        return response()->json(new TaskResource($task));
    }

    /**
     * Assignee-ləri yenilə
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

    /**
     * Drag & Drop: status dəyişikliyi
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
     * Alt tapşırıqlar
     */
    public function subtasks(Request $request, Task $task): JsonResponse
    {
        $subtasks = $task->subtasks()
            ->with(['assignees', 'creator'])
            ->withCount(['attachments', 'comments'])
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
        ]);

        $data['parent_task_id'] = $task->id;

        $subtask = $this->taskService->createTask($task->space, $data, $request->user());

        return response()->json(new TaskResource($subtask->load(['assignees', 'creator'])), 201);
    }
}
