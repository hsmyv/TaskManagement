<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\Board;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardActivityController extends Controller
{
    public function index(Request $request, Board $board): JsonResponse
    {
        $this->authorize('viewActivity', $board);

        $logs = ActivityLog::query()
            ->where('board_id', $board->id)
            ->with('employee')
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return response()->json([
            'data' => ActivityLogResource::collection($logs),
        ]);
    }
}

