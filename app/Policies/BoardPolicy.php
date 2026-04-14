<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\Employee;

class BoardPolicy
{
    public function view(Employee $employee, Board $board): bool
    {
        return $employee->hasGlobalAccess()
            || $employee->isMemberOf($board->space);
    }

    public function create(Employee $employee, \App\Models\Space $space): bool
    {
        return $employee->hasGlobalAccess()
            || $employee->isSpaceManager($space);
    }

    public function update(Employee $employee, Board $board): bool
    {
        return $employee->hasGlobalAccess()
            || $employee->isSpaceManager($board->space)
            || $board->created_by === $employee->id;
    }

    public function manageMembers(Employee $employee, Board $board): bool
    {
        return $this->update($employee, $board);
    }

    public function viewActivity(Employee $employee, Board $board): bool
    {
        return $employee->hasGlobalAccess()
            || $employee->isSpaceManager($board->space)
            || $board->created_by === $employee->id;
    }
}

