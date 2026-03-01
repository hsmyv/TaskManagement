<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


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
