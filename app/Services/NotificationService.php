<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmailQueue;
use App\Models\Notification;
use App\Models\Task;

class NotificationService
{
    public function notify(Employee $recipient, string $event, Task $task, array $data = []): void
    {
        // Əməliyyatı edən şəxsə bildiriş göndərmə
        if ($recipient->id === request()->user()?->id) {
            return;
        }

        Notification::create([
            'employee_id'            => $recipient->id,
            'type'                   => 'App\\Notifications\\TaskNotification',
            'notifiable_entity_type' => Task::class,
            'notifiable_entity_id'   => $task->id,
            'event'                  => $event,
            'data'                   => array_merge([
                'task_id'    => $task->id,
                'task_title' => $task->title,
                'space_name' => $task->space?->name,
            ], $data),
        ]);
    }

    public function notifyMany(iterable $recipients, string $event, Task $task, array $data = []): void
    {
        foreach ($recipients as $recipient) {
            $this->notify($recipient, $event, $task, $data);
        }
    }

    public function notifyTaskCreated(Task $task, Employee $creator): void
    {
        $this->notifyMany($task->assignees, 'task_created', $task, [
            'created_by' => $creator->full_name,
        ]);

        foreach ($task->assignees as $recipient) {
            $this->queueEmail($recipient, 'task_created', $task, [
                'task_title' => $task->title,
                'created_by' => $creator->full_name,
                'due_date'   => $task->due_date?->format('d.m.Y'),
                'space_name' => $task->space?->name,
                'task_url'   => route('tasks.show', $task),
            ]);
        }
    }

    public function notifyTaskUpdated(Task $task, Employee $updater, array $changes): void
    {
        $recipients = $task->assignees->merge(collect([$task->creator]))->unique('id');
        $this->notifyMany($recipients, 'task_updated', $task, [
            'updated_by' => $updater->full_name,
            'changes'    => array_keys($changes),
        ]);
    }

    public function notifyStatusChanged(Task $task, Employee $changer, string $from, string $to): void
    {
        $recipients = $task->assignees
            ->merge(collect([$task->assigner ?? $task->creator]))
            ->unique('id');
        $this->notifyMany($recipients, 'status_changed', $task, [
            'changed_by'  => $changer->full_name,
            'from_status' => $from,
            'to_status'   => $to,
        ]);
    }

    public function notifyTaskApproved(Task $task, Employee $approver): void
    {
        $this->notifyMany($task->assignees, 'task_approved', $task, [
            'approved_by' => $approver->full_name,
        ]);
    }

    public function notifyAssigneesChanged(Task $task, Employee $assigner): void
    {
        $this->notifyMany($task->assignees, 'assignee_changed', $task, [
            'assigned_by' => $assigner->full_name,
        ]);
    }

    public function notifyCommentAdded(Task $task, Employee $commenter): void
    {
        $recipients = $task->assignees
            ->merge(collect([$task->creator]))
            ->unique('id')
            ->reject(fn($e) => $e->id === $commenter->id);

        $this->notifyMany($recipients, 'comment_added', $task, [
            'commented_by' => $commenter->full_name,
        ]);
    }

    public function queueEmail(Employee $recipient, string $template, Task $task, array $payload = [], ?string $scheduledAt = null): void
    {
        EmailQueue::create([
            'employee_id'  => $recipient->id,
            'to_email'     => $recipient->email,
            'to_name'      => $recipient->full_name,
            'subject'      => $this->emailSubject($template, $task),
            'template'     => $template,
            'payload'      => $payload,
            'scheduled_at' => $scheduledAt,
        ]);
    }

    public function scheduleDeadlineReminder(Task $task): void
    {
        if (!$task->due_date) return;

        $recipients = $task->assignees;

        $reminder24h = $task->due_date->subDay()->setTime(9, 0);
        if ($reminder24h->isFuture()) {
            foreach ($recipients as $recipient) {
                $this->queueEmail($recipient, 'deadline_reminder', $task, [
                    'task_title' => $task->title,
                    'due_date'   => $task->due_date->format('d.m.Y'),
                    'hours_left' => 24,
                    'task_url'   => route('tasks.show', $task),
                ], $reminder24h->toDateTimeString());
            }
        }

        $reminder3h = $task->due_date->subHours(3);
        if ($reminder3h->isFuture()) {
            foreach ($recipients as $recipient) {
                $this->queueEmail($recipient, 'deadline_reminder', $task, [
                    'task_title' => $task->title,
                    'due_date'   => $task->due_date->format('d.m.Y H:i'),
                    'hours_left' => 3,
                    'task_url'   => route('tasks.show', $task),
                ], $reminder3h->toDateTimeString());
            }
        }
    }

    private function emailSubject(string $template, Task $task): string
    {
        return match ($template) {
            'task_created'      => "Yeni tapşırıq: {$task->title}",
            'task_updated'      => "Tapşırıq yeniləndi: {$task->title}",
            'status_changed'    => "Status dəyişdi: {$task->title}",
            'deadline_reminder' => "⚠️ Deadline xatırlatması: {$task->title}",
            'task_overdue'      => "🔴 Gecikmiş tapşırıq: {$task->title}",
            'task_approved'     => "✅ Tapşırıq təsdiqləndi: {$task->title}",
            default             => "TİS bildirişi: {$task->title}",
        };
    }
}
