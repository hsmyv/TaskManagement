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


// ── TaskApproved ──────────────────────────────────────────────────────────────

class TaskApproved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Task $task,
        public readonly Employee $approver
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
            'task_id'     => $this->task->id,
            'approved_by' => $this->approver->full_name,
        ];
    }

    public function broadcastAs(): string { return 'task.approved'; }
}
