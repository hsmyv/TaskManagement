<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'space_id' => $this->space_id,
            'name' => $this->name,
            'description' => $this->description,
            'deadline' => $this->deadline?->toDateString(),
            'created_by' => $this->created_by,
            'creator' => new EmployeeResource($this->whenLoaded('creator')),
            'archived_at' => $this->archived_at?->toDateTimeString(),
            'is_archived' => filled($this->archived_at),
            'is_deadline_passed' => $this->deadline ? $this->deadline->isPast() : false,
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
            'tasks_count' => $this->whenCounted('tasks'),
            'completed_tasks_count' => $this->completed_tasks_count ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'can' => [
                'view_activity' => $request->user() ? $request->user()->can('viewActivity', $this->resource) : false,
                'update' => $request->user() ? $request->user()->can('update', $this->resource) : false,
                'manage_members' => $request->user() ? $request->user()->can('manageMembers', $this->resource) : false,
                'archive' => $request->user() ? $request->user()->can('update', $this->resource) : false,
            ],
        ];
    }
}
