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


// ── TaskUpdated ───────────────────────────────────────────────────────────────

class TaskUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Task $task,
        public readonly array $changes = []
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("space.{$this->task->space_id}"),
            new PrivateChannel("task.{$this->task->id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'task_id' => $this->task->id,
            'changes' => $this->changes,
            'task'    => [
                'id'               => $this->task->id,
                'title'            => $this->task->title,
                'status'           => $this->task->status,
                'priority'         => $this->task->priority,
                'due_date'         => $this->task->due_date?->toDateString(),
                'description'      => $this->task->description,
                'require_approval' => $this->task->require_approval,
                'deadline_locked'  => $this->task->deadline_locked,
            ],
        ];
    }

    public function broadcastAs(): string { return 'task.updated'; }
}

