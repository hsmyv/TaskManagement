<?php

namespace App\Http\Controllers\Api;

use App\Events\CommentAdded;
use App\Events\ChecklistToggled;
use App\Http\Controllers\Controller;
use App\Http\Resources\AttachmentResource;
use App\Http\Resources\ChecklistResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\NotificationResource;
use App\Models\Attachment;
use App\Models\Checklist;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Task;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// ── DashboardController ───────────────────────────────────────────────────────

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $employee = $request->user();

        $myTasks = Task::forEmployee($employee)
            ->with(['space', 'assignees'])
            ->withCount('subtasks')
            ->whereNull('parent_task_id')
            ->get();

        return response()->json([
            'stats' => [
                'todo'                => $myTasks->where('status', 'todo')->count(),
                'in_progress'         => $myTasks->where('status', 'in_progress')->count(),
                'waiting_for_approve' => $myTasks->where('status', 'waiting_for_approve')->count(),
                'completed'           => $myTasks->where('status', 'completed')->count(),
                'overdue'             => $myTasks->filter(fn($t) => $t->isOverdue())->count(),
            ],
            'due_soon' => \App\Http\Resources\TaskResource::collection(
                $myTasks->filter(fn($t) => $t->due_date
                    && $t->due_date->isFuture()
                    && $t->due_date->diffInDays(now()) <= 7
                    && !in_array($t->status, ['completed', 'canceled'])
                )->take(10)->values()
            ),
            'overdue' => \App\Http\Resources\TaskResource::collection(
                $myTasks->filter(fn($t) => $t->isOverdue())->take(10)->values()
            ),
            'my_spaces' => \App\Http\Resources\SpaceResource::collection(
                $employee->hasGlobalAccess()
                    ? \App\Models\Space::withCount('tasks')->where('is_active', true)->get()
                    : $employee->spaces()->withCount('tasks')->get()
            ),
            'unread_notifications' => Notification::where('employee_id', $employee->id)
                ->unread()->count(),
        ]);
    }
}
