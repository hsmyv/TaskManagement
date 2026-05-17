<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\Space;
use App\Models\Task;

class TaskPolicy
{
    public function create(Employee $employee, Space $space): bool
    {
        return $employee->hasGlobalAccess()
            || $employee->isMemberOf($space);
    }

    public function view(Employee $employee, Task $task): bool
    {
        if ($employee->hasGlobalAccess()) {
            return true;
        }

        $isDirectParticipant = $task->created_by === $employee->id || $task->isAssignee($employee);
        $isSubtaskParticipant = $task->isSubtask()
            ? $isDirectParticipant
            : $task->subtasks()
                ->where(function ($query) use ($employee) {
                    $query->where('created_by', $employee->id)
                        ->orWhereHas('assignees', fn ($assignees) => $assignees->where('employees.id', $employee->id));
                })
                ->exists();

        if (!$employee->isMemberOf($task->space) && !$isDirectParticipant && !$isSubtaskParticipant) {
            return false;
        }

        if ($task->visibility === Task::VISIBILITY_MANAGERS) {
            $spaceRole = $employee->spaceRole($task->space);
            $managerRoles = ['senior_manager', 'middle_manager'];

            if (!in_array($spaceRole, $managerRoles, true) && !$employee->hasRole(['administrator', 'executive_manager'])) {
                return $isDirectParticipant || $isSubtaskParticipant;
            }
        }

        return true;
    }

    public function update(Employee $employee, Task $task): bool
    {
        if ($employee->hasGlobalAccess()) {
            return true;
        }

        if ($task->isSubtask()) {
            return $task->created_by === $employee->id || $task->isAssignee($employee);
        }

        return $task->created_by === $employee->id;
    }

    public function changeStatus(Employee $employee, Task $task): bool
    {
        if ($employee->hasGlobalAccess()) {
            return true;
        }

        $isParticipant = $task->created_by === $employee->id || $task->isAssignee($employee);

        if (!$employee->isMemberOf($task->space) && !$isParticipant) {
            return false;
        }

        return $isParticipant;
    }

    public function toggleChecklist(Employee $employee, Task $task): bool
    {
        if ($employee->hasGlobalAccess()) {
            return true;
        }

        if (!$employee->isMemberOf($task->space)) {
            return false;
        }

        return $task->created_by === $employee->id || $task->isAssignee($employee);
    }

    public function updateDeadline(Employee $employee, Task $task): bool
    {
        if ($task->deadline_locked) {
            return $task->assigned_by === $employee->id || $employee->hasGlobalAccess();
        }

        return $this->update($employee, $task);
    }

    public function assign(Employee $employee, Task $task): bool
    {
        if ($employee->hasGlobalAccess()) {
            return true;
        }

        if ($task->isSubtask()) {
            return $task->created_by === $employee->id || $task->isAssignee($employee);
        }

        return $task->created_by === $employee->id;
    }

    public function approve(Employee $employee, Task $task): bool
    {
        if ($task->isSubtask()) {
            return $task->created_by === $employee->id || $task->isAssignee($employee) || $employee->hasGlobalAccess();
        }

        return $task->created_by === $employee->id;
    }

    public function delete(Employee $employee, Task $task): bool
    {
        if ($employee->hasGlobalAccess()) {
            return true;
        }

        $spaceRole = $employee->spaceRole($task->space);

        if ($spaceRole === 'senior_manager') {
            return true;
        }

        return $task->created_by === $employee->id;
    }

    public function move(Employee $employee, Task $task): bool
    {
        if ($employee->hasGlobalAccess()) {
            return true;
        }

        if (!$employee->isMemberOf($task->space)) {
            return false;
        }

        return $task->created_by === $employee->id
            || $task->isAssignee($employee)
            || $employee->isSpaceManager($task->space);
    }
}
