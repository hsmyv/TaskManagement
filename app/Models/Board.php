<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Board extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'space_id',
        'name',
        'description',
        'deadline',
        'created_by',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'board_members', 'board_id', 'employee_id')
            ->withPivot(['joined_at', 'added_by'])
            ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'board_id')->orderBy('board_position');
    }

    public function hasMember(Employee $employee): bool
    {
        return $this->members()->where('employees.id', $employee->id)->exists();
    }
}

