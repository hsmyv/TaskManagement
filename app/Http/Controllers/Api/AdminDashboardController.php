<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Http\Resources\TaskResource;
use App\Models\ActivityLog;
use App\Models\Board;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\Space;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tasksByStatus = Task::query()
            ->whereNull('parent_task_id')
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $dueTomorrow = Task::query()
            ->whereNull('parent_task_id')
            ->whereNotIn('status', [Task::STATUS_COMPLETED, Task::STATUS_CANCELED])
            ->whereDate('due_date', now()->addDay()->toDateString())
            ->with(['space', 'board', 'assignees'])
            ->latest()
            ->limit(8)
            ->get();

        $overdue = Task::query()
            ->whereNull('parent_task_id')
            ->overdue()
            ->with(['space', 'board', 'assignees'])
            ->oldest('due_date')
            ->limit(8)
            ->get();

        $recentLogs = ActivityLog::query()
            ->with(['employee', 'space', 'board', 'task'])
            ->latest('created_at')
            ->limit(8)
            ->get();

        $recentNotifications = Notification::query()
            ->with('employee')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Notification $notification) => [
                'id' => $notification->id,
                'event' => $notification->event,
                'data' => $notification->data,
                'employee' => [
                    'id' => $notification->employee?->id,
                    'full_name' => $notification->employee?->full_name,
                ],
                'is_read' => !is_null($notification->read_at),
                'created_at' => $notification->created_at,
            ]);

        return response()->json([
            'cards' => [
                'employees' => Employee::count(),
                'active_employees' => Employee::where('is_active', true)->count(),
                'spaces' => Space::count(),
                'active_spaces' => Space::where('is_active', true)->count(),
                'boards' => Board::count(),
                'active_boards' => Board::whereNull('archived_at')->count(),
                'tasks' => Task::whereNull('parent_task_id')->count(),
                'overdue_tasks' => Task::whereNull('parent_task_id')->overdue()->count(),
                'due_tomorrow_tasks' => Task::whereNull('parent_task_id')
                    ->whereNotIn('status', [Task::STATUS_COMPLETED, Task::STATUS_CANCELED])
                    ->whereDate('due_date', now()->addDay()->toDateString())
                    ->count(),
                'unread_notifications' => Notification::unread()->count(),
            ],
            'tasks_by_status' => [
                'todo' => (int) ($tasksByStatus[Task::STATUS_TODO] ?? 0),
                'in_progress' => (int) ($tasksByStatus[Task::STATUS_IN_PROGRESS] ?? 0),
                'waiting_for_approve' => (int) ($tasksByStatus[Task::STATUS_WAITING_FOR_APPROVE] ?? 0),
                'completed' => (int) ($tasksByStatus[Task::STATUS_COMPLETED] ?? 0),
                'canceled' => (int) ($tasksByStatus[Task::STATUS_CANCELED] ?? 0),
            ],
            'due_tomorrow' => TaskResource::collection($dueTomorrow)->resolve($request),
            'overdue' => TaskResource::collection($overdue)->resolve($request),
            'recent_logs' => ActivityLogResource::collection($recentLogs)->resolve($request),
            'recent_notifications' => $recentNotifications,
        ]);
    }
}
