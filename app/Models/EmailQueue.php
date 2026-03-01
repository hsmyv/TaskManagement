<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailQueue extends Model
{
    protected $table = 'email_queue';

    protected $fillable = [
        'employee_id',
        'to_email',
        'to_name',
        'subject',
        'template',
        'payload',
        'status',
        'attempts',
        'error_message',
        'scheduled_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'payload'      => 'array',
            'scheduled_at' => 'datetime',
            'sent_at'      => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                     ->where(function ($q) {
                         $q->whereNull('scheduled_at')
                           ->orWhere('scheduled_at', '<=', now());
                     })
                     ->where('attempts', '<', 3);
    }
}
