<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_at' => optional($this->created_at)->toISOString(),
            'action' => $this->action,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'meta' => $this->meta,
            'space' => $this->whenLoaded('space', function () {
                return [
                    'id' => $this->space?->id,
                    'name' => $this->space?->name,
                ];
            }),
            'board' => $this->whenLoaded('board', function () {
                return [
                    'id' => $this->board?->id,
                    'name' => $this->board?->name,
                ];
            }),
            'task' => $this->when($this->entity_type === 'task' && $this->relationLoaded('task') && $this->task, function () {
                return [
                    'id' => $this->task->id,
                    'task_code' => $this->task->task_code,
                    'title' => $this->task->title,
                    'status' => $this->task->status,
                ];
            }),
            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'id' => $this->employee->id,
                    'full_name' => $this->employee->full_name,
                    'avatar_url' => $this->employee->avatar_url,
                ];
            }),
        ];
    }
}

