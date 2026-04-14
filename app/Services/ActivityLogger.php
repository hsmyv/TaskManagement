<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Board;
use App\Models\Employee;
use App\Models\Space;

class ActivityLogger
{
    public function log(
        Employee $actor,
        string $action,
        string $entityType,
        int $entityId,
        ?Space $space = null,
        ?Board $board = null,
        ?array $meta = null
    ): ActivityLog {
        return ActivityLog::create([
            'space_id'    => $space?->id,
            'board_id'    => $board?->id,
            'employee_id' => $actor->id,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'meta'        => $meta,
            'created_at'  => now(),
        ]);
    }
}

