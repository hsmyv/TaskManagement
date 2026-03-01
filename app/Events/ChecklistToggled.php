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

// ── ChecklistToggled ──────────────────────────────────────────────────────────

class ChecklistToggled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly \App\Models\Checklist $checklist) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("task.{$this->checklist->task_id}")];
    }

    public function broadcastWith(): array
    {
        return [
            'checklist_id' => $this->checklist->id,
            'task_id'      => $this->checklist->task_id,
            'is_done'      => $this->checklist->is_done,
        ];
    }

    public function broadcastAs(): string { return 'checklist.toggled'; }
}
