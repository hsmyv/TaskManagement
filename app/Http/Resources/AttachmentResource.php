<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
