<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BoardListResource;
use App\Models\Board;
use App\Models\BoardList;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function reorder(Request $request, Board $board, ActivityLogger $logger): JsonResponse
    {
        $this->authorize('update', $board);

        $validated = $request->validate([
            'list_ids' => ['required', 'array', 'min:1'],
            'list_ids.*' => ['integer', 'exists:board_lists,id'],
        ]);

        $listIds = collect($validated['list_ids'])->values();

        // Ensure all lists belong to this board
        $boardListIds = $board->lists()->pluck('id')->all();
        foreach ($listIds as $id) {
            if (!in_array((int) $id, $boardListIds, true)) {
                return response()->json(['message' => 'List bu board-a aid deyil.'], 422);
            }
        }

        DB::transaction(function () use ($listIds) {
            foreach ($listIds as $pos => $id) {
                BoardList::whereKey((int) $id)->update(['position' => (int) $pos]);
            }
        });

        $logger->log($request->user(), 'reorder', 'board_list', (int) ($listIds->first() ?? 0), $board->space, $board, [
            'list_ids' => $listIds->all(),
        ]);

        return response()->json(['message' => 'List sırası yeniləndi.']);
    }
}

