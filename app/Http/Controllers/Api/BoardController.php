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
public function index(Request $request, Space $space): JsonResponse
{
    $this->authorize('view', $space);

    $boards = $space->boards()
        ->when(!$request->boolean('archived'), fn ($query) => $query->whereNull('archived_at'))
        ->when($request->boolean('archived'), fn ($query) => $query->whereNotNull('archived_at'))
        ->with([
            'tasks' => fn ($query) => $query
                ->whereNull('parent_task_id')
                ->with('assignees')
                ->withCount([
                    'subtasks',
                    'attachments',
                    'allComments as comments_count',
                    'subtasks as completed_subtasks_count' => fn ($query) => $query->where('status', 'completed'),
                ]),
            'creator',
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
        'data' => BoardResource::collection($boards),
    ]);
}

    public function store(Request $request, Space $space, ActivityLogger $logger): JsonResponse
    {
        $this->authorize('create', [\App\Models\Board::class, $space]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'deadline' => ['nullable', 'date'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:employees,id'],
        ]);

        $board = Board::create([
            'space_id' => $space->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'deadline' => $validated['deadline'] ?? null,
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

    public function archive(Request $request, Board $board, ActivityLogger $logger): JsonResponse
    {
        $this->authorize('update', $board);

        $board->update(['archived_at' => now()]);

        $logger->log($request->user(), 'archive', 'board', $board->id, $board->space, $board, [
            'name' => $board->name,
        ]);

        return response()->json(['data' => new BoardResource($board->fresh(['creator']))]);
    }

    public function unarchive(Request $request, Board $board, ActivityLogger $logger): JsonResponse
    {
        $this->authorize('update', $board);

        $board->update(['archived_at' => null]);

        $logger->log($request->user(), 'unarchive', 'board', $board->id, $board->space, $board, [
            'name' => $board->name,
        ]);

        return response()->json(['data' => new BoardResource($board->fresh(['creator']))]);
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
            'creator',
            'tasks' => function ($q) use ($request) {
                $q->whereNull('parent_task_id')
                    ->with(['assignees'])
                    ->withCount([
                        'subtasks',
                        'attachments',
                        'allComments as comments_count',
                        'subtasks as completed_subtasks_count' => fn ($query) => $query->where('status', 'completed'),
                    ])
                    ->forEmployee($request->user())
                    ->orderBy('board_position');
            },
        ]);

        return response()->json([
            'data' => new BoardResource($board),
        ]);
    }
}
