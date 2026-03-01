<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    // ── Status sabitləri ──────────────────────────────────────────────────

    const STATUS_TODO               = 'todo';
    const STATUS_IN_PROGRESS        = 'in_progress';
    const STATUS_WAITING_FOR_APPROVE = 'waiting_for_approve';
    const STATUS_COMPLETED          = 'completed';
    const STATUS_CANCELED           = 'canceled';

    const PRIORITY_LOW    = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH   = 'high';
    const PRIORITY_URGENT = 'urgent';

    const VISIBILITY_ALL      = 'all_members';
    const VISIBILITY_MANAGERS = 'managers_only';

    protected $fillable = [
        'title',
        'description',
        'space_id',
        'parent_task_id',
        'status',
        'priority',
        'start_date',
        'due_date',
        'estimated_hours',
        'visibility',
        'require_approval',
        'deadline_locked',
        'created_by',
        'assigned_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date'       => 'date',
            'due_date'         => 'date',
            'require_approval' => 'boolean',
            'deadline_locked'  => 'boolean',
        ];
    }

    // ── Relations ─────────────────────────────────────────────────────────

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * Əsas task (subtask isə)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    /**
     * Alt tapşırıqlar
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_by');
    }

    /**
     * Tapşırığın məsul şəxsləri (pivot ilə)
     */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'task_assignees', 'task_id', 'employee_id')
                    ->withPivot(['assigned_by', 'assigned_at'])
                    ->withTimestamps();
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(Checklist::class)->orderBy('order');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id')->latest();
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(StatusHistory::class)->latest('changed_at');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeRootTasks($query)
    {
        return $query->whereNull('parent_task_id');
    }

    public function scopeSubtasks($query)
    {
        return $query->whereNotNull('parent_task_id');
    }

    public function scopeForEmployee($query, Employee $employee)
    {
        if ($employee->hasGlobalAccess()) {
            return $query;
        }

        return $query->where(function ($q) use ($employee) {
            $q->where('created_by', $employee->id)
              ->orWhereHas('assignees', fn($aq) => $aq->where('employees.id', $employee->id));
        });
    }

    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELED])
                     ->whereNotNull('due_date')
                     ->whereBetween('due_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELED])
                     ->whereNotNull('due_date')
                     ->where('due_date', '<', now()->toDateString());
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function isSubtask(): bool
    {
        return !is_null($this->parent_task_id);
    }

    public function isAssignee(Employee $employee): bool
    {
        return $this->assignees()->where('employees.id', $employee->id)->exists();
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && !in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELED]);
    }

    public function getChecklistProgressAttribute(): array
    {
        $total = $this->checklists()->count();
        $done  = $this->checklists()->where('is_done', true)->count();
        return [
            'total'      => $total,
            'done'       => $done,
            'percentage' => $total > 0 ? round(($done / $total) * 100) : 0,
        ];
    }
}
