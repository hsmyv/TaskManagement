<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoardListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'board_id' => $this->board_id,
            'title' => $this->title,
            'type' => $this->type,
            'position' => $this->position,
            'tasks' => \App\Http\Resources\TaskResource::collection($this->whenLoaded('tasks')),
        ];
    }
}

