<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Board;
use App\Models\Task;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardTaskController extends Controller
{
    public function store(Request $request, Board $board, ActivityLogger $logger): JsonResponse
    {
        $board->load('space');
        $space = $board->space;

        $this->authorize('view', $board);
        $this->authorize('create', [Task::class, $space]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'visibility' => ['nullable', 'in:all_members,managers_only'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'assignee_ids' => ['nullable', 'array'],
            'assignee_ids.*' => ['integer', 'exists:employees,id'],
        ]);

        $pos = (int) (Task::where('board_id', $board->id)->max('board_position') ?? 0) + 1;

        $task = Task::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'space_id' => $space->id,
            'board_id' => $board->id,
            'board_position' => $pos,
            'priority' => $validated['priority'] ?? 'medium',
            'visibility' => $validated['visibility'] ?? Task::VISIBILITY_ALL,
            'start_date' => $validated['start_date'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'created_by' => $request->user()->id,
            'assigned_by' => $request->user()->id,
        ]);

        if (!empty($validated['assignee_ids'])) {
            $task->assignees()->sync($validated['assignee_ids']);
        }

        $logger->log($request->user(), 'create', 'task', $task->id, $space, $board, [
            'title' => $task->title,
            'board_id' => $board->id,
        ]);

        $task->load(['assignees', 'space']);

        return response()->json([
            'data' => new TaskResource($task),
        ], 201);
    }

    public function move(Request $request, Task $task, ActivityLogger $logger): JsonResponse
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'board_id' => ['nullable', 'integer', 'exists:boards,id'],
            'board_position' => ['nullable', 'integer', 'min:0'],
        ]);

        $before = $task->only(['board_id', 'board_position']);

        $newBoardId = array_key_exists('board_id', $validated) ? $validated['board_id'] : $task->board_id;

        // If moving into a board, ensure user can view it
        $contextBoard = null;
        if ($newBoardId) {
            $contextBoard = Board::with('space')->findOrFail((int) $newBoardId);
            $this->authorize('view', $contextBoard);
        }

        $task->board_id = $newBoardId ? (int) $newBoardId : null;
        $task->board_position = isset($validated['board_position'])
            ? (int) $validated['board_position']
            : ($task->board_id
                ? ((int) (Task::where('board_id', $task->board_id)->max('board_position') ?? 0) + 1)
                : 0);
        $task->save();

        $logger->log(
            $request->user(),
            'move',
            'task',
            $task->id,
            $contextBoard?->space ?? $task->space,
            $contextBoard ?? $task->space,
            [
            'before' => $before,
            'after' => $task->only(['board_id', 'board_position']),
            ]
        );

        $task->load(['assignees', 'space']);

        return response()->json([
            'data' => new TaskResource($task),
        ]);
    }
}

