<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\Space;
use App\Models\Task;

class SpacePolicy
{
    public function create(Employee $employee): bool
    {
        return $employee->hasRole('administrator');
    }

    public function update(Employee $employee, Space $space): bool
    {
        if ($employee->hasGlobalAccess()) {
            return true;
        }

        $spaceRole = $employee->spaceRole($space);
        return $spaceRole === 'senior_manager'
            || $employee->isSpaceManager($space);
    }

    public function delete(Employee $employee, Space $space): bool
    {
        return $employee->hasRole('administrator');
    }

    public function manageMembers(Employee $employee, Space $space): bool
    {
        if ($employee->hasGlobalAccess()) {
            return true;
        }

        return $employee->spaceRole($space) === 'senior_manager'
            || $employee->isSpaceManager($space);
    }

    public function view(Employee $employee, Space $space): bool
    {
        if ($employee->hasGlobalAccess() || $employee->isMemberOf($space)) {
            return true;
        }

        return Task::query()
            ->where('space_id', $space->id)
            ->whereNotNull('parent_task_id')
            ->where(function ($query) use ($employee) {
                $query->where('created_by', $employee->id)
                    ->orWhereHas('assignees', fn ($assignees) => $assignees->where('employees.id', $employee->id))
                    ->orWhereHas('helpers', fn ($helpers) => $helpers->where('employees.id', $employee->id))
                    ->orWhereHas('supervisors', fn ($supervisors) => $supervisors->where('employees.id', $employee->id));
            })
            ->exists();
    }
}
