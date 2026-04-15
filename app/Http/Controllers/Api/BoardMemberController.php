<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Board;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardMemberController extends Controller
{
    public function index(Request $request, Board $board): JsonResponse
    {
        $this->authorize('view', $board);

        $members = $board->members()->get();

        return response()->json([
            'data' => EmployeeResource::collection($members),
        ]);
    }

    public function sync(Request $request, Board $board): JsonResponse
    {
        $this->authorize('manageMembers', $board);

        $validated = $request->validate([
            'member_ids' => ['required', 'array'],
            'member_ids.*' => ['integer', 'exists:employees,id'],
        ]);

        // Ensure creator always stays a member
        $memberIds = collect($validated['member_ids'])
            ->push($board->created_by)
            ->unique()
            ->values();

        // Only members from this space can be added
        $spaceMemberIds = $board->space->members()->pluck('employees.id')->all();
        $memberIds = $memberIds->filter(fn ($id) => in_array($id, $spaceMemberIds, true))->values();

        $now = now();
        $syncData = $memberIds
            ->mapWithKeys(function ($id) use ($request, $now) {
                return [
                    (int) $id => [
                        'added_by' => $request->user()->id,
                        'joined_at' => $now,
                    ],
                ];
            })
            ->all();

        $board->members()->sync($syncData);

        return response()->json([
            'message' => 'Board üzvləri yeniləndi.',
        ]);
    }
}

