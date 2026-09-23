<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\Board;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'entity_type' => 'nullable|in:task,board,board_list',
            'action' => 'nullable|string|max:80',
            'task_id' => 'nullable|integer|exists:tasks,id',
            'board_id' => 'nullable|integer|exists:boards,id',
            'space_id' => 'nullable|integer|exists:spaces,id',
            'employee_id' => 'nullable|integer|exists:employees,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'q' => 'nullable|string|max:120',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = ActivityLog::query()
            ->with(['employee', 'space', 'board', 'task'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (!empty($data['entity_type'])) {
            $query->where('entity_type', $data['entity_type']);
        }

        if (!empty($data['action'])) {
            $query->where('action', $data['action']);
        }

        if (!empty($data['task_id'])) {
            $query->where('entity_type', 'task')
                ->where('entity_id', $data['task_id']);
        }

        if (!empty($data['board_id'])) {
            $query->where(function ($query) use ($data) {
                $query->where('board_id', $data['board_id'])
                    ->orWhere(function ($query) use ($data) {
                        $query->where('entity_type', 'board')
                            ->where('entity_id', $data['board_id']);
                    });
            });
        }

        if (!empty($data['space_id'])) {
            $query->where('space_id', $data['space_id']);
        }

        if (!empty($data['employee_id'])) {
            $query->where('employee_id', $data['employee_id']);
        }

        if (!empty($data['from'])) {
            $query->where('created_at', '>=', $data['from'] . ' 00:00:00');
        }

        if (!empty($data['to'])) {
            $query->where('created_at', '<=', $data['to'] . ' 23:59:59');
        }

        if (!empty($data['q'])) {
            $term = $data['q'];
            $query->where(function ($query) use ($term) {
                $query->where('action', 'like', "%{$term}%")
                    ->orWhere('entity_type', 'like', "%{$term}%")
                    ->orWhere('meta', 'like', "%{$term}%")
                    ->orWhereHas('employee', fn ($employee) => $employee->where('name', 'like', "%{$term}%")
                        ->orWhere('surname', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%"))
                    ->orWhereHas('board', fn ($board) => $board->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('task', fn ($task) => $task->where('title', 'like', "%{$term}%"));
            });
        }

        $logs = $query->paginate($data['per_page'] ?? 30);

        return response()->json([
            'data' => ActivityLogResource::collection($logs->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    public function options(): JsonResponse
    {
        return response()->json([
            'actions' => ActivityLog::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
            'boards' => Board::query()
                ->select(['id', 'name', 'space_id'])
                ->with('space:id,name')
                ->latest('id')
                ->limit(200)
                ->get(),
            'tasks' => Task::query()
                ->select(['id', 'title', 'status', 'board_id', 'space_id'])
                ->latest('id')
                ->limit(200)
                ->get()
                ->map(fn (Task $task) => [
                    'id' => $task->id,
                    'task_code' => $task->task_code,
                    'title' => $task->title,
                    'status' => $task->status,
                    'board_id' => $task->board_id,
                    'space_id' => $task->space_id,
                ]),
        ]);
    }
}
