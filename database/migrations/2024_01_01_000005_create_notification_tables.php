<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Notifications (TIS section 5.5, 9) ────────────────────────────
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')             // Bildirişin alıcısı
                  ->constrained('employees')
                  ->cascadeOnDelete();

            $table->string('type');                       // NotificationType class adı
            $table->morphs('notifiable_entity');          // task, comment, attachment, etc.

            // Bildiriş tipi
            $table->enum('event', [
                'task_created',
                'task_updated',
                'task_deleted',
                'assignee_changed',
                'status_changed',
                'comment_added',
                'attachment_added',
                'attachment_deleted',
                'approval_requested',
                'task_approved',
                'deadline_reminder',
                'task_overdue',
            ]);

            $table->json('data');                         // Əlavə məlumatlar (JSON)
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'read_at']);
            $table->index(['employee_id', 'created_at']);
        });

        // ── Email Queue (TIS section 9) ────────────────────────────────────
        Schema::create('email_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                  ->nullable()
                  ->constrained('employees')
                  ->nullOnDelete();
            $table->string('to_email');
            $table->string('to_name');
            $table->string('subject');
            $table->string('template');                   // Mail template adı
            $table->json('payload');                      // Template dəyişənləri
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('scheduled_at')->nullable(); // Göndərmə vaxtı (reminder üçün)
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_queue');
        Schema::dropIfExists('notifications');
    }
};
