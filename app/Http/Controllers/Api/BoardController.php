<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BoardResource;
use App\Models\Board;
use App\Models\Space;
use App\Models\Task;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardController extends Controller
{
public function index(Space $space): JsonResponse
{
    $this->authorize('view', $space);

    $boards = $space->boards()
        ->with([
            'tasks.assignees',
        ])
        ->withCount([
            'tasks',
            'tasks as todo_tasks_count' => fn ($q) => $q->where('status', 'todo'),
            'tasks as in_progress_tasks_count' => fn ($q) => $q->where('status', 'in_progress'),
            'tasks as waiting_for_approve_tasks_count' => fn ($q) => $q->where('status', 'waiting_for_approve'),
            'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'completed'),
            'tasks as canceled_tasks_count' => fn ($q) => $q->where('status', 'canceled'),
        ])
        ->latest()
        ->get();

    return response()->json([
        'data' => $boards
    ]);
}

    public function store(Request $request, Space $space, ActivityLogger $logger): JsonResponse
    {
        $this->authorize('create', [\App\Models\Board::class, $space]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:employees,id'],
        ]);

        $board = Board::create([
            'space_id' => $space->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        // Ensure creator is a member
        $memberIds = collect($validated['member_ids'] ?? [])
            ->push($request->user()->id)
            ->unique()
            ->values();

        // Only members from this space can be added
        $spaceMemberIds = $space->members()->pluck('employees.id')->all();
        $memberIds = $memberIds->filter(fn ($id) => in_array($id, $spaceMemberIds, true));

        $board->members()->sync($memberIds);

        $logger->log($request->user(), 'create', 'board', $board->id, $space, $board, [
            'name' => $board->name,
        ]);

        $board->load(['space']);

        return response()->json([
            'data' => new BoardResource($board),
        ], 201);
    }

    public function show(Request $request, Board $board): JsonResponse
    {
        $this->authorize('view', $board);

        $board->loadCount([
            'tasks',
            'tasks as completed_tasks_count' => fn ($q) => $q->where('status', Task::STATUS_COMPLETED),
        ]);

        $board->load([
            'space',
            'tasks' => function ($q) {
                $q->whereNull('parent_task_id')
                    ->with(['assignees'])
                    ->orderBy('board_position');
            },
        ]);

        return response()->json([
            'data' => new BoardResource($board),
        ]);
    }
}

