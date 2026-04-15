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
            'created_by' => $this->created_by,
            'lists' => BoardListResource::collection($this->whenLoaded('lists')),
            'can' => [
                'view_activity' => $request->user() ? $request->user()->can('viewActivity', $this->resource) : false,
                'update' => $request->user() ? $request->user()->can('update', $this->resource) : false,
                'manage_members' => $request->user() ? $request->user()->can('manageMembers', $this->resource) : false,
            ],
        ];
    }
}

