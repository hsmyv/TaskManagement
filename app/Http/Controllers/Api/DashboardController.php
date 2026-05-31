<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SpaceResource;
use App\Http\Resources\TaskResource;
use App\Models\Notification;
use App\Models\Space;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $employee = $request->user();
        $onlyEmployeeTasks = function ($query) use ($employee) {
            $query->where('created_by', $employee->id)
                ->orWhere('assigned_by', $employee->id)
                ->orWhereHas('assignees', fn ($assignees) => $assignees->where('employees.id', $employee->id))
                ->orWhereHas('subtasks', function ($subtasks) use ($employee) {
                    $subtasks->where('created_by', $employee->id)
                        ->orWhere('assigned_by', $employee->id)
                        ->orWhereHas('assignees', fn ($assignees) => $assignees->where('employees.id', $employee->id));
                });
        };

        // ── Yalnız mənə aid tapşırıqlar (yaratdıqlarım + assign olunduqlarım) ──
        $myTasks = Task::query()
            ->with(['space.department', 'board', 'assignees', 'creator', 'assigner'])
            ->withCount('subtasks')
            ->whereNull('parent_task_id')
            ->where($onlyEmployeeTasks)
            ->get();

        // ── Space-lər: members_count + mənə aid tasks_count ──────────────────
        if ($employee->hasGlobalAccess()) {
            // Admin / Executive — bütün space-lər, bütün tapşırıqlar
            $spaces = Space::withCount('members')
                ->withCount('boards')
                ->withCount('tasks')
                ->where('is_active', true)
                ->get();
        } else {
            // Digər rolllar — yalnız üzv olduqları space-lər
            // tasks_count → yalnız həmin space-də mənə aid tapşırıqlar
            $spaces = $employee->spaces()
                ->withCount('members')
                ->withCount('boards')
                ->withCount([
                    'tasks as tasks_count' => function ($query) use ($employee) {
                        $query->whereNull('parent_task_id')
                              ->where(function ($q) use ($employee) {
                                  // Mən yaratmışam VƏ ya mənə assign olunub
                                  $q->where('created_by', $employee->id)
                                    ->orWhereHas('assignees', function ($a) use ($employee) {
                                        $a->where('employees.id', $employee->id);
                                    })
                                    ->orWhereHas('subtasks', function ($s) use ($employee) {
                                        $s->where('created_by', $employee->id)
                                          ->orWhereHas('assignees', function ($a) use ($employee) {
                                              $a->where('employees.id', $employee->id);
                                          });
                                    });
                              });
                    },
                ])
                ->where('is_active', true)
                ->get();
        }

        $taskQuery = Task::query()
            ->with(['space.department', 'board', 'assignees', 'creator', 'assigner'])
            ->withCount(['subtasks', 'attachments', 'allComments as comments_count'])
            ->whereNull('parent_task_id')
            ->where($onlyEmployeeTasks);

        if ($request->filled('space_id')) {
            $taskQuery->where('space_id', $request->integer('space_id'));
        }
        if ($request->filled('status')) {
            $taskQuery->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $taskQuery->where('priority', $request->priority);
        }
        if ($request->boolean('overdue')) {
            $taskQuery->overdue();
        }
        if ($request->filled('due_days')) {
            $days = max(1, $request->integer('due_days', 7));
            $taskQuery->whereNotNull('due_date')
                ->where('due_date', '<=', now()->addDays($days)->toDateString());
        }
        if ($request->filled('q')) {
            $search = $request->q;
            $taskQuery->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhereHas('board', fn ($board) => $board->where('name', 'like', "%{$search}%"));
            });
        }

        $tasks = $taskQuery->latest()->get();
        $groupedTasks = $tasks
            ->groupBy('status')
            ->map(fn ($group) => TaskResource::collection($group)->resolve($request));

        return response()->json([
            'stats' => [
                'todo'                => $myTasks->where('status', 'todo')->count(),
                'in_progress'         => $myTasks->where('status', 'in_progress')->count(),
                'waiting_for_approve' => $myTasks->where('status', 'waiting_for_approve')->count(),
                'completed'           => $myTasks->where('status', 'completed')->count(),
                'overdue'             => $myTasks->filter(fn($t) => $t->isOverdue())->count(),
            ],

            'due_soon' => TaskResource::collection(
                $myTasks->filter(fn($t) =>
                    $t->due_date
                    && $t->due_date->isFuture()
                    && $t->due_date->diffInDays(now()) <= 7
                    && !in_array($t->status, ['completed', 'canceled'])
                )->take(10)->values()
            ),

            'overdue' => TaskResource::collection(
                $myTasks->filter(fn($t) => $t->isOverdue())->take(10)->values()
            ),

            'my_spaces' => SpaceResource::collection($spaces),
            'tasks' => TaskResource::collection($tasks),
            'grouped_tasks' => $groupedTasks,
            'space_stats' => Space::query()
                ->where('is_active', true)
                ->withCount([
                    'tasks as tasks_total' => fn ($query) => $query->whereNull('parent_task_id'),
                    'tasks as todo_count' => fn ($query) => $query->whereNull('parent_task_id')->where('status', 'todo'),
                    'tasks as in_progress_count' => fn ($query) => $query->whereNull('parent_task_id')->where('status', 'in_progress'),
                    'tasks as waiting_count' => fn ($query) => $query->whereNull('parent_task_id')->where('status', 'waiting_for_approve'),
                    'tasks as completed_count' => fn ($query) => $query->whereNull('parent_task_id')->where('status', 'completed'),
                    'tasks as canceled_count' => fn ($query) => $query->whereNull('parent_task_id')->where('status', 'canceled'),
                    'tasks as overdue_count' => fn ($query) => $query->whereNull('parent_task_id')
                        ->whereNotIn('status', ['completed', 'canceled'])
                        ->whereNotNull('due_date')
                        ->where('due_date', '<', now()->toDateString()),
                    'boards',
                ])
                ->get(['id', 'name']),

            'unread_notifications' => Notification::where('employee_id', $employee->id)
                ->unread()
                ->count(),
        ]);
    }
}
