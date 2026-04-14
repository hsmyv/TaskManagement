<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BoardListResource;
use App\Models\Board;
use App\Models\BoardList;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardListController extends Controller
{
    public function store(Request $request, Board $board, ActivityLogger $logger): JsonResponse
    {
        $this->authorize('create', [BoardList::class, $board]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'in:todo,in_progress,done,rejected,custom'],
        ]);

        $position = (int) ($board->lists()->max('position') ?? 0) + 1;

        $list = BoardList::create([
            'board_id' => $board->id,
            'title' => $validated['title'],
            'type' => $validated['type'] ?? 'custom',
            'position' => $position,
            'created_by' => $request->user()->id,
        ]);

        $logger->log($request->user(), 'create', 'board_list', $list->id, $board->space, $board, [
            'title' => $list->title,
            'type' => $list->type,
        ]);

        return response()->json([
            'data' => new BoardListResource($list),
        ], 201);
    }

    public function update(Request $request, BoardList $list, ActivityLogger $logger): JsonResponse
    {
        $this->authorize('update', $list);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'in:todo,in_progress,done,rejected,custom'],
            'position' => ['sometimes', 'required', 'integer', 'min:0'],
        ]);

        $before = $list->only(['title', 'type', 'position']);
        $list->fill($validated)->save();

        $logger->log($request->user(), 'update', 'board_list', $list->id, $list->board->space, $list->board, [
            'before' => $before,
            'after' => $list->only(['title', 'type', 'position']),
        ]);

        return response()->json([
            'data' => new BoardListResource($list),
        ]);
    }

    public function destroy(Request $request, BoardList $list, ActivityLogger $logger): JsonResponse
    {
        $this->authorize('delete', $list);

        $board = $list->board;

        $list->delete();

        $logger->log($request->user(), 'delete', 'board_list', $list->id, $board->space, $board, [
            'title' => $list->title,
        ]);

        return response()->json([], 204);
    }
}

