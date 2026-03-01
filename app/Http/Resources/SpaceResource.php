<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'slug'         => $this->slug,
            'description'  => $this->description,
            'color'        => $this->color,
            'icon'         => $this->icon,
            'is_active'    => $this->is_active,
            'created_by'   => new EmployeeResource($this->whenLoaded('creator')),
            'members_count'=> $this->whenCounted('members'),
            'tasks_count'  => $this->whenCounted('tasks'),
            'my_role'      => $this->when(
                $request->user(),
                fn() => $request->user()->spaceRole($this->resource)
            ),
            'can'          => [
                'update'         => $request->user()?->can('update', $this->resource),
                'delete'         => $request->user()?->can('delete', $this->resource),
                'manage_members' => $request->user()?->can('manageMembers', $this->resource),
            ],
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
