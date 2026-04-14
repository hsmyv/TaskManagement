<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Employee extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $table = 'employees';

    protected $fillable = [
        'name',
        'surname',
        'patronymic',
        'email',
        'password',
        'phone',
        'position',
        'department_id',
        'avatar',
        'external_id',
        'source_type',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active'     => 'boolean',
            'last_login_at' => 'datetime',
            'password'      => 'hashed',
        ];
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return trim("{$this->name} {$this->surname}");
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        return "https://ui-avatars.com/api/?name={$this->name}+{$this->surname}&background=3B82F6&color=fff";
    }

    // ── Relations ─────────────────────────────────────────────────────────

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function spaces(): BelongsToMany
    {
        return $this->belongsToMany(Space::class, 'space_members', 'employee_id', 'space_id')
                    ->withPivot(['space_role', 'is_manager', 'joined_at', 'added_by'])
                    ->withTimestamps();
    }

    public function boards(): BelongsToMany
    {
        return $this->belongsToMany(Board::class, 'board_members', 'employee_id', 'board_id')
            ->withPivot(['joined_at', 'added_by'])
            ->withTimestamps();
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_by');
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_assignees', 'employee_id', 'task_id')
                    ->withPivot(['assigned_by', 'assigned_at']);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'employee_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'employee_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function isMemberOf(Space $space): bool
    {
        return $this->spaces()->where('spaces.id', $space->id)->exists();
    }

    public function spaceRole(Space $space): ?string
    {
        $member = $this->spaces()->where('spaces.id', $space->id)->first();
        return $member?->pivot->space_role;
    }

    public function isSpaceManager(Space $space): bool
    {
        return $this->spaces()
            ->where('spaces.id', $space->id)
            ->wherePivot('is_manager', true)
            ->exists();
    }

    public function hasGlobalAccess(): bool
    {
        return $this->hasAnyRole(['administrator', 'executive_manager']);
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
