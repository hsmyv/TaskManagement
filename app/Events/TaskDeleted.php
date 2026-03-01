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

// ── TaskDeleted ───────────────────────────────────────────────────────────────

class TaskDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $taskId,
        public readonly int $spaceId,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("space.{$this->spaceId}")];
    }

    public function broadcastWith(): array
    {
        return ['task_id' => $this->taskId, 'space_id' => $this->spaceId];
    }

    public function broadcastAs(): string { return 'task.deleted'; }
}
