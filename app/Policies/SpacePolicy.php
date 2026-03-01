<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\Space;

class SpacePolicy
{
    /**
     * Space yaratmaq — yalnız Administrator
     */
    public function create(Employee $employee): bool
    {
        return $employee->hasRole('administrator');
    }

    /**
     * Space redaktə etmək
     */
    public function update(Employee $employee, Space $space): bool
    {
        if ($employee->hasGlobalAccess()) {
            return true;
        }

        $spaceRole = $employee->spaceRole($space);
        return $spaceRole === 'senior_manager';
    }

    /**
     * Space silmək — yalnız Administrator
     */
    public function delete(Employee $employee, Space $space): bool
    {
        return $employee->hasRole('administrator');
    }

    /**
     * Üzv əlavə etmək/silmək
     */
    public function manageMembers(Employee $employee, Space $space): bool
    {
        if ($employee->hasGlobalAccess()) {
            return true;
        }

        return $employee->spaceRole($space) === 'senior_manager';
    }

    /**
     * Space-ə baxmaq — üzv olanlar
     */
    public function view(Employee $employee, Space $space): bool
    {
        return $employee->hasGlobalAccess()
            || $employee->isMemberOf($space);
    }
}
