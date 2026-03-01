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
// ── NewNotification ───────────────────────────────────────────────────────────

class NewNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Notification $notification) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("employee.{$this->notification->employee_id}")];
    }

    public function broadcastWith(): array
    {
        return [
            'id'         => $this->notification->id,
            'event'      => $this->notification->event,
            'data'       => $this->notification->data,
            'created_at' => $this->notification->created_at,
        ];
    }

    public function broadcastAs(): string { return 'notification.new'; }
}
