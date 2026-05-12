<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\Task;
use App\Models\Space;

class TaskPolicy
{
    /**
     * Task yaratmaq — yalnız Space üzvü
     */
    public function create(Employee $employee, Space $space): bool
    {
        return $employee->hasGlobalAccess()
            || $employee->isMemberOf($space);
    }

    /**
     * Task-ı görmək
     */
    public function view(Employee $employee, Task $task): bool
    {
        if ($employee->hasGlobalAccess()) {
            return true;
        }

        // Space üzvü deyilsə — hər halda hayır
        if (!$employee->isMemberOf($task->space)) {
            return false;
        }

        // "managers_only" visibility
        if ($task->visibility === Task::VISIBILITY_MANAGERS) {
            $spaceRole = $employee->spaceRole($task->space);
            $managerRoles = ['senior_manager', 'middle_manager'];
            if (!in_array($spaceRole, $managerRoles) && !$employee->hasRole(['administrator', 'executive_manager'])) {
                // Yalnız özünün yaratdığı və ya assign edildiyi task-ları görə bilər
                return $task->created_by === $employee->id
                    || $task->isAssignee($employee);
            }
        }

        return true;
    }

    /**
     * Task-ı redaktə etmək
     */
public function update(Employee $employee, Task $task): bool
{
    if ($employee->hasGlobalAccess()) {
        return true;
    }

    // Yalnız task-ı yaradan redaktə edə bilər (detallar + məsul şəxslər)
    return $task->created_by === $employee->id;
}

    /**
     * Status dəyişmək / icra prosesində update (assignee edə bilər)
     */
    public function changeStatus(Employee $employee, Task $task): bool
    {
        if ($employee->hasGlobalAccess()) return true;
        if (!$employee->isMemberOf($task->space)) return false;
        return $task->created_by === $employee->id || $task->isAssignee($employee);
    }

    /**
     * Deadline-ı dəyişmək
     */
    public function updateDeadline(Employee $employee, Task $task): bool
    {
        // Deadline kilidlidirsə — yalnız assign edən
        if ($task->deadline_locked) {
            return $task->assigned_by === $employee->id
                || $employee->hasGlobalAccess();
        }

        // Kilidli deyilsə — yalnız yaradan (update icazəsi)
        return $this->update($employee, $task);
    }

    /**
     * Assignee dəyişmək/əlavə etmək
     */
    public function assign(Employee $employee, Task $task): bool
    {
        if ($employee->hasGlobalAccess()) return true;
        return $task->created_by === $employee->id;
    }

    /**
     * Tapşırığı təsdiqləmək (Waiting for approve → Completed)
     */
    public function approve(Employee $employee, Task $task): bool
    {
        // require_approval olmasa — approve düyməsi heç kimə görünməsin
        if (!$task->require_approval) return false;

        if ($employee->hasGlobalAccess()) return true;

        // Assignees-dən biri approve edə bilər
        return $task->isAssignee($employee);
    }
    /**
     * Task silmək
     */
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
