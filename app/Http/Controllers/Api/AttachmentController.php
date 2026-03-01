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

// ── AttachmentController ──────────────────────────────────────────────────────

class AttachmentController extends Controller
{
    public function index(Task $task): JsonResponse
    {
        $attachments = $task->attachments()->with('uploader')->latest()->get();
        return response()->json(AttachmentResource::collection($attachments));
    }

    public function store(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'file' => [
                'required', 'file',
                'max:' . (Attachment::MAX_SIZE_MB * 1024),
                'mimes:txt,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,mpg,mpeg,avi,pdf',
            ],
        ]);

        $file       = $request->file('file');
        $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path       = $file->storeAs("attachments/task-{$task->id}", $storedName, 'local');

        $attachment = $task->attachments()->create([
            'original_name' => $file->getClientOriginalName(),
            'stored_name'   => $storedName,
            'disk'          => 'local',
            'path'          => $path,
            'mime_type'     => $file->getMimeType(),
            'size'          => $file->getSize(),
            'uploaded_by'   => $request->user()->id,
        ]);

        $attachment->load('uploader');
        return response()->json(new AttachmentResource($attachment), 201);
    }

    public function destroy(Request $request, Attachment $attachment): JsonResponse
    {
        abort_unless(
            $attachment->uploaded_by === $request->user()->id || $request->user()->hasGlobalAccess(),
            403
        );
        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();
        return response()->json(['message' => 'Fayl silindi.']);
    }

    public function download(Attachment $attachment): mixed
    {
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);
        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }
}
