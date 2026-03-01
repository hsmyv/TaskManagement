<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// ── Status History ────────────────────────────────────────────────────────────

class StatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'from_status' => $this->from_status,
            'from_label'  => $this->from_status ? \App\Models\StatusHistory::statusLabel($this->from_status) : null,
            'to_status'   => $this->to_status,
            'to_label'    => \App\Models\StatusHistory::statusLabel($this->to_status),
            'changed_by'  => new EmployeeResource($this->whenLoaded('changedBy')),
            'comment'     => $this->comment,
            'changed_at'  => $this->changed_at,
        ];
    }
}
