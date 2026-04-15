<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpaceMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $pivot = $this->pivot;

        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'position' => $this->position,
            'avatar_url' => $this->avatar_url,
            'is_active' => $this->is_active,

            'space_role' => $pivot?->space_role,
            'is_manager' => (bool) ($pivot?->is_manager ?? false),
            'can_create_boards' => (bool) ($pivot?->can_create_boards ?? false),
            'joined_at' => $pivot?->joined_at,
        ];
    }
}

