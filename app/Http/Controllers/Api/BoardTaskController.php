<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\BoardList;
use App\Models\Task;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardTaskController extends Controller
{
    public function store(Request $request, BoardList $list, ActivityLogger $logger): JsonResponse
    {
        $board = $list->board()->with('space')->firstOrFail();
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

        $pos = (int) (Task::where('board_list_id', $list->id)->max('board_position') ?? 0) + 1;

        $task = Task::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'space_id' => $space->id,
            'board_id' => $board->id,
            'board_list_id' => $list->id,
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
            'board_list_id' => $list->id,
        ]);

        $task->load(['assignees', 'space']);

        return response()->json([
            'data' => new TaskResource($task),
        ], 201);
    }

    public function move(Request $request, Task $task, ActivityLogger $logger): JsonResponse
    {
        $task->load(['board', 'board.space']);
        $board = $task->board;

        if (!$board) {
            return response()->json(['message' => 'Bu tapşırıq board-a bağlı deyil.'], 422);
        }

        $this->authorize('view', $board);
        $this->authorize('update', $task);

        $validated = $request->validate([
            'board_list_id' => ['required', 'integer', 'exists:board_lists,id'],
            'board_position' => ['nullable', 'integer', 'min:0'],
        ]);

        $newList = BoardList::with('board.space')->findOrFail((int) $validated['board_list_id']);

        if ($newList->board_id !== $board->id) {
            return response()->json(['message' => 'List bu board-a aid deyil.'], 422);
        }

        $before = $task->only(['board_list_id', 'board_position']);

        $task->board_list_id = $newList->id;
        $task->board_position = isset($validated['board_position'])
            ? (int) $validated['board_position']
            : ((int) (Task::where('board_list_id', $newList->id)->max('board_position') ?? 0) + 1);
        $task->save();

        $logger->log($request->user(), 'move', 'task', $task->id, $board->space, $board, [
            'before' => $before,
            'after' => $task->only(['board_list_id', 'board_position']),
        ]);

        $task->load(['assignees', 'space']);

        return response()->json([
            'data' => new TaskResource($task),
        ]);
    }
}

