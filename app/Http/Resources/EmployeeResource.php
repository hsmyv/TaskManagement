<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// ── Employee Resource ─────────────────────────────────────────────────────────

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'surname'    => $this->surname,
            'phone'      => $this->phone,
            'patronymic' => $this->patronymic,
            'full_name'  => $this->full_name,
            'email'      => $this->email,
            'position'   => $this->position,
            'department' => $this->department,
            'avatar_url' => $this->avatar_url,
            'is_active'  => $this->is_active,
            'roles'      => $this->getRoleNames(),
        ];
    }
}
