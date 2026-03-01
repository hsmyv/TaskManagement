<?php

namespace App\Console\Commands;

use App\Models\EmailQueue;
use App\Models\Task;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDeadlineReminders extends Command
{
    protected $signature   = 'tis:deadline-reminders';
    protected $description = 'Deadline yaxınlaşan tapşırıqlar üçün xatırlatma emaili göndər';

    public function __construct(private readonly NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Deadline xatırlatmaları yoxlanılır...');

        // Gecikmiş tasklar — gündəlik xatırlatma
        $overdues = Task::overdue()
            ->with(['assignees', 'space'])
            ->get();

        foreach ($overdues as $task) {
            foreach ($task->assignees as $assignee) {
                $this->notificationService->queueEmail($assignee, 'task_overdue', $task, [
                    'task_title' => $task->title,
                    'due_date'   => $task->due_date->format('d.m.Y'),
                    'days_late'  => $task->due_date->diffInDays(now()),
                    'task_url'   => route('tasks.show', $task),
                ]);
            }
        }

        $this->info("Gecikmiş: {$overdues->count()} task");

        // Pending emailləri göndər
        $this->processEmailQueue();
    }

    private function processEmailQueue(): void
    {
        $pending = EmailQueue::pending()->limit(100)->get();
        $sent    = 0;
        $failed  = 0;

        foreach ($pending as $email) {
            try {
                Mail::send(
                    "emails.{$email->template}",
                    $email->payload,
                    function ($message) use ($email) {
                        $message->to($email->to_email, $email->to_name)
                                ->subject($email->subject);
                    }
                );

                $email->update(['status' => 'sent', 'sent_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                $email->increment('attempts');
                $email->update([
                    'status'        => $email->attempts >= 3 ? 'failed' : 'pending',
                    'error_message' => $e->getMessage(),
                ]);
                Log::error("Email göndərilə bilmədi: {$email->to_email}", ['error' => $e->getMessage()]);
                $failed++;
            }
        }

        $this->info("Email: {$sent} göndərildi, {$failed} uğursuz");
    }
}
