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



// ── CommentController ─────────────────────────────────────────────────────────

class CommentController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function index(Task $task): JsonResponse
    {
        $comments = Comment::query()
            ->where('task_id', $task->id)
            ->with('author')
            ->oldest()
            ->get();

        $grouped = $comments->groupBy('parent_id');

        $attachReplies = function (Comment $comment) use (&$attachReplies, $grouped): Comment {
            $replies = ($grouped->get($comment->id) ?? collect())
                ->values()
                ->map(fn (Comment $reply) => $attachReplies($reply));

            $comment->setRelation('replies', $replies);

            return $comment;
        };

        $tree = ($grouped->get('') ?? $grouped->get(null) ?? collect())
            ->values()
            ->map(fn (Comment $comment) => $attachReplies($comment));

        return response()->json(CommentResource::collection($tree));
    }

    public function store(Request $request, Task $task): JsonResponse
    {
        $data = $request->validate([
            'body'      => 'required|string|max:5000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        if (!empty($data['parent_id'])) {
            abort_unless(
                Comment::where('id', $data['parent_id'])->where('task_id', $task->id)->exists(),
                422,
                'Cavab verilən şərh bu tapşırığa aid deyil.'
            );
        }

        $comment = Comment::create([
            'task_id'     => $task->id,
            'employee_id' => $request->user()->id,
            'body'        => $data['body'],
            'parent_id'   => $data['parent_id'] ?? null,
        ]);

        $comment->load(['author', 'replies.author']);
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
