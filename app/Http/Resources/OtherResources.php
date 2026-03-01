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

// ── Attachment ────────────────────────────────────────────────────────────────

class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'original_name' => $this->original_name,
            'mime_type'     => $this->mime_type,
            'size'          => $this->size,
            'size_human'    => $this->size_human,
            'url'           => $this->url,
            'uploaded_by'   => new EmployeeResource($this->whenLoaded('uploader')),
            'created_at'    => $this->created_at,
            'can'           => [
                'delete' => $request->user()?->id === $this->uploaded_by
                    || $request->user()?->hasGlobalAccess(),
            ],
        ];
    }
}

// ── Comment ───────────────────────────────────────────────────────────────────

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'task_id'          => $this->task_id,
            'body'             => $this->body,
            'is_status_comment'=> $this->is_status_comment,
            'author'           => new EmployeeResource($this->whenLoaded('author')),
            'replies'          => CommentResource::collection($this->whenLoaded('replies')),
            'created_at'       => $this->created_at,
            'can'              => [
                'delete' => $request->user()?->id === $this->employee_id
                    || $request->user()?->hasGlobalAccess(),
            ],
        ];
    }
}

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
