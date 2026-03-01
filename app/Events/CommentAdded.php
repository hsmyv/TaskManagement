<?php

namespace App\Events;

use App\Models\Employee;
use App\Models\Notification;
use App\Models\Task;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// ── CommentAdded ──────────────────────────────────────────────────────────────

class CommentAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly \App\Models\Comment $comment
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("task.{$this->comment->task_id}")];
    }

    public function broadcastWith(): array
    {
        return [
            'comment' => [
                'id'         => $this->comment->id,
                'body'       => $this->comment->body,
                'task_id'    => $this->comment->task_id,
                'author'     => [
                    'id'         => $this->comment->author->id,
                    'full_name'  => $this->comment->author->full_name,
                    'avatar_url' => $this->comment->author->avatar_url,
                ],
                'created_at' => $this->comment->created_at,
            ],
        ];
    }

    public function broadcastAs(): string { return 'comment.added'; }
}
