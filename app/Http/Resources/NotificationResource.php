<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// ── Notification ──────────────────────────────────────────────────────────────

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id,
            'event'   => $this->event,
            'data'    => $this->data,
            'read_at' => $this->read_at,
            'is_read' => !is_null($this->read_at),
            'created_at' => $this->created_at,
        ];
    }
}
