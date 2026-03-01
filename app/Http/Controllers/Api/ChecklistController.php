<?php

namespace App\Http\Controllers\Api;

use App\Events\CommentAdded;
use App\Events\ChecklistToggled;
use App\Http\Controllers\Controller;
use App\Http\Resources\AttachmentResource;
use App\Http\Resources\ChecklistResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\NotificationResource;
use App\Models\Attachment;
use App\Models\Checklist;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Task;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// ── ChecklistController ───────────────────────────────────────────────────────

class ChecklistController extends Controller
{
    public function store(Request $request, Task $task): JsonResponse
    {
        $data  = $request->validate(['title' => 'required|string|max:255']);
        $order = $task->checklists()->max('order') + 1;

        $checklist = $task->checklists()->create([
            'title' => $data['title'],
            'order' => $order,
        ]);

        return response()->json(new ChecklistResource($checklist), 201);
    }

    public function toggle(Request $request, Checklist $checklist): JsonResponse
    {
        $checklist->update([
            'is_done'      => !$checklist->is_done,
            'completed_by' => !$checklist->is_done ? $request->user()->id : null,
            'completed_at' => !$checklist->is_done ? now() : null,
        ]);

        return response()->json(new ChecklistResource($checklist));
    }

    public function update(Request $request, Checklist $checklist): JsonResponse
    {
        $data = $request->validate(['title' => 'required|string|max:255']);
        $checklist->update($data);
        return response()->json(new ChecklistResource($checklist));
    }

    public function destroy(Checklist $checklist): JsonResponse
    {
        $checklist->delete();
        return response()->json(['message' => 'Silindi.']);
    }

    public function reorder(Request $request, Task $task): JsonResponse
    {
        $data = $request->validate([
            'items'      => 'required|array',
            'items.*.id' => 'required|exists:checklists,id',
        ]);

        foreach ($data['items'] as $i => $item) {
            Checklist::where('id', $item['id'])->update(['order' => $i]);
        }

        return response()->json(['message' => 'Sıralama yeniləndi.']);
    }
}
