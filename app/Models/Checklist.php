<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// ── Checklist ─────────────────────────────────────────────────────────────────

class Checklist extends Model
{
    protected $fillable = ['task_id', 'title', 'is_done', 'order', 'completed_by', 'completed_at'];

    protected function casts(): array
    {
        return [
            'is_done'      => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'completed_by');
    }
}
