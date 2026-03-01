<?php

namespace App\Http\Controllers\Api;

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

// ── DashboardController ───────────────────────────────────────────────────────

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $employee = $request->user();

        $myTasks = Task::forEmployee($employee)
            ->with(['space', 'assignees'])
            ->withCount('subtasks')
            ->whereNull('parent_task_id')
            ->get();

        return response()->json([
            'stats' => [
                'todo'                => $myTasks->where('status', 'todo')->count(),
                'in_progress'         => $myTasks->where('status', 'in_progress')->count(),
                'waiting_for_approve' => $myTasks->where('status', 'waiting_for_approve')->count(),
                'completed'           => $myTasks->where('status', 'completed')->count(),
                'overdue'             => $myTasks->filter(fn($t) => $t->isOverdue())->count(),
            ],
            'due_soon' => \App\Http\Resources\TaskResource::collection(
                $myTasks->filter(fn($t) => $t->due_date
                    && $t->due_date->isFuture()
                    && $t->due_date->diffInDays(now()) <= 7
                    && !in_array($t->status, ['completed', 'canceled'])
                )->take(10)->values()
            ),
            'overdue' => \App\Http\Resources\TaskResource::collection(
                $myTasks->filter(fn($t) => $t->isOverdue())->take(10)->values()
            ),
            'my_spaces' => \App\Http\Resources\SpaceResource::collection(
                $employee->hasGlobalAccess()
                    ? \App\Models\Space::withCount('tasks')->where('is_active', true)->get()
                    : $employee->spaces()->withCount('tasks')->get()
            ),
            'unread_notifications' => Notification::where('employee_id', $employee->id)
                ->unread()->count(),
        ]);
    }
}

// ── CommentController ─────────────────────────────────────────────────────────

class CommentController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function index(Task $task): JsonResponse
    {
        $comments = $task->comments()
            ->with(['author', 'replies.author'])
            ->latest()
            ->get();

        return response()->json(CommentResource::collection($comments));
    }

    public function store(Request $request, Task $task): JsonResponse
    {
        $data = $request->validate([
            'body'      => 'required|string|max:5000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $comment = $task->comments()->create([
            'employee_id' => $request->user()->id,
            'body'        => $data['body'],
            'parent_id'   => $data['parent_id'] ?? null,
        ]);

        $comment->load(['author', 'replies']);
        $this->notificationService->notifyCommentAdded($task, $request->user());

        return response()->json(new CommentResource($comment), 201);
    }

    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        abort_unless(
            $comment->employee_id === $request->user()->id || $request->user()->hasGlobalAccess(),
            403
        );
        $comment->delete();
        return response()->json(['message' => 'Şərh silindi.']);
    }
}

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

// ── NotificationController ────────────────────────────────────────────────────

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::where('employee_id', $request->user()->id)
            ->latest()
            ->paginate(30);

        return response()->json([
            'data'  => NotificationResource::collection($notifications),
            'unread'=> Notification::where('employee_id', $request->user()->id)->unread()->count(),
            'meta'  => [
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'total'        => $notifications->total(),
            ],
        ]);
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        abort_unless($notification->employee_id === $request->user()->id, 403);
        $notification->markAsRead();
        return response()->json(['message' => 'Oxundu.']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        Notification::where('employee_id', $request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);
        return response()->json(['message' => 'Hamısı oxundu.']);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('employee_id', $request->user()->id)->unread()->count();
        return response()->json(['count' => $count]);
    }
}
