<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Space extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'is_active',
        'created_by',
        'department_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ── Boot ──────────────────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Space $space) {
            if (empty($space->slug)) {
                $space->slug = Str::slug($space->name) . '-' . Str::random(4);
            }
        });
    }

    // ── Relations ─────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'space_members', 'space_id', 'employee_id')
                    ->withPivot(['space_role', 'joined_at', 'added_by'])
                    ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'space_id');
    }

    public function rootTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'space_id')
                    ->whereNull('parent_task_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function hasMember(Employee $employee): bool
    {
        return $this->members()->where('employees.id', $employee->id)->exists();
    }

    public function getMemberRole(Employee $employee): ?string
    {
        $member = $this->members()->where('employees.id', $employee->id)->first();
        return $member?->pivot->space_role;
    }
}
