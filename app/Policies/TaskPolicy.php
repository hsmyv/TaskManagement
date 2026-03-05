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

    // Tapşırığı verən şəxs həmişə update edə bilər (drag, status, approve)
    if ($task->assigned_by === $employee->id) {
        return true;
    }

    $spaceRole = $employee->spaceRole($task->space);

    if ($spaceRole === 'senior_manager') {
        return true;
    }

    if ($spaceRole === 'middle_manager') {
        return $task->created_by === $employee->id;
    }

    return $task->created_by === $employee->id
        || $task->isAssignee($employee);
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

        // Kilidli deyilsə — redaktə icazəsi olanlar
        return $this->update($employee, $task);
    }

    /**
     * Assignee dəyişmək/əlavə etmək
     */
    public function assign(Employee $employee, Task $task): bool
    {
        if ($employee->hasGlobalAccess()) return true;

        // Space yüklü deyilsə əlavə yüklə
        $space = $task->relationLoaded('space') ? $task->space : $task->load('space')->space;
        if (!$space) return false;

        $spaceRole = $employee->spaceRole($space);

        // Senior — həmişə
        if ($spaceRole === 'senior_manager') return true;

        // Middle + Employee — yalnız öz yaratdığı tapşırıqda
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
}
