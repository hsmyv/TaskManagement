<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// ── Checklist ─────────────────────────────────────────────────────────────────

class ChecklistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'task_id'      => $this->task_id,
            'title'        => $this->title,
            'is_done'      => $this->is_done,
            'order'        => $this->order,
            'completed_by' => new EmployeeResource($this->whenLoaded('completedBy')),
            'completed_at' => $this->completed_at,
            'created_at'   => $this->created_at,
        ];
    }
}
