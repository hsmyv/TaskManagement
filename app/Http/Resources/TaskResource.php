<?php

namespace App\Http\Resources;

use App\Models\StatusHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'description'      => $this->description,
            'space_id'         => $this->space_id,
            'parent_task_id'   => $this->parent_task_id,
            'status'           => $this->status,
            'status_label'     => StatusHistory::statusLabel($this->status),
            'priority'         => $this->priority,
            'start_date'       => $this->start_date?->toDateString(),
            'due_date'         => $this->due_date?->toDateString(),
            'estimated_hours'  => $this->estimated_hours,
            'visibility'       => $this->visibility,
            'require_approval' => $this->require_approval,
            'deadline_locked'  => $this->deadline_locked,
            'is_overdue'       => $this->isOverdue(),
            'is_subtask'       => $this->isSubtask(),

            // İlişkilər
            'space'         => new SpaceResource($this->whenLoaded('space')),
            'creator'       => new EmployeeResource($this->whenLoaded('creator')),
            'assigner'      => new EmployeeResource($this->whenLoaded('assigner')),
            'assignees'     => EmployeeResource::collection($this->whenLoaded('assignees')),
            'subtasks'      => TaskResource::collection($this->whenLoaded('subtasks')),
            'subtasks_count'=> $this->whenCounted('subtasks'),
            'checklists'    => ChecklistResource::collection($this->whenLoaded('checklists')),
            'checklist_progress' => $this->when(
                $this->relationLoaded('checklists'),
                fn() => $this->checklist_progress
            ),
            'attachments'      => AttachmentResource::collection($this->whenLoaded('attachments')),
            'attachments_count'=> $this->whenCounted('attachments'),
            'comments_count'   => $this->whenCounted('comments'),
            'status_history'   => StatusHistoryResource::collection($this->whenLoaded('statusHistory')),

            // İcazələr
            'can' => [
                'update'          => $request->user()?->can('update', $this->resource),
                'delete'          => $request->user()?->can('delete', $this->resource),
                'assign'          => $request->user()?->can('assign', $this->resource),
                'approve'         => $request->user()?->can('approve', $this->resource),
                'update_deadline' => $request->user()?->can('updateDeadline', $this->resource),
            ],

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
