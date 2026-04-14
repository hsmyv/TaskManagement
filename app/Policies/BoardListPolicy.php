<?php

namespace App\Policies;

use App\Models\BoardList;
use App\Models\Employee;

class BoardListPolicy
{
    public function create(Employee $employee, \App\Models\Board $board): bool
    {
        return $employee->hasGlobalAccess()
            || $employee->isMemberOf($board->space);
    }

    public function update(Employee $employee, BoardList $list): bool
    {
        return $employee->hasGlobalAccess()
            || $employee->isMemberOf($list->board->space);
    }

    public function delete(Employee $employee, BoardList $list): bool
    {
        return $this->update($employee, $list);
    }
}

