<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// ── Status History ────────────────────────────────────────────────────────────

class StatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'task_id',
        'from_status',
        'to_status',
        'changed_by',
        'comment',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'changed_by');
    }

    // Status etiketlərini Azərbaycanca qaytar
    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'todo'                => 'Görüləcək',
            'in_progress'         => 'İcra olunur',
            'waiting_for_approve' => 'Təsdiq gözləyir',
            'completed'           => 'Tamamlandı',
            'canceled'            => 'Ləğv olundu',
            default               => $status,
        };
    }
}
