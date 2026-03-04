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

        // ── Yalnız mənə aid tapşırıqlar (yaratdıqlarım + assign olunduqlarım) ──
        $myTasks = Task::forEmployee($employee)
            ->with(['space', 'assignees'])
            ->withCount('subtasks')
            ->whereNull('parent_task_id')
            ->get();

        // ── Space-lər: members_count + mənə aid tasks_count ──────────────────
        if ($employee->hasGlobalAccess()) {
            // Admin / Executive — bütün space-lər, bütün tapşırıqlar
            $spaces = Space::withCount('members')
                ->withCount('tasks')
                ->where('is_active', true)
                ->get();
        } else {
            // Digər rolllar — yalnız üzv olduqları space-lər
            // tasks_count → yalnız həmin space-də mənə aid tapşırıqlar
            $spaces = $employee->spaces()
                ->withCount('members')
                ->withCount([
                    'tasks as tasks_count' => function ($query) use ($employee) {
                        $query->whereNull('parent_task_id')
                              ->where(function ($q) use ($employee) {
                                  // Mən yaratmışam VƏ ya mənə assign olunub
                                  $q->where('created_by', $employee->id)
                                    ->orWhereHas('assignees', function ($a) use ($employee) {
                                        $a->where('employees.id', $employee->id);
                                    });
                              });
                    },
                ])
                ->where('is_active', true)
                ->get();
        }

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

            'unread_notifications' => Notification::where('employee_id', $employee->id)
                ->unread()
                ->count(),
        ]);
    }
}
