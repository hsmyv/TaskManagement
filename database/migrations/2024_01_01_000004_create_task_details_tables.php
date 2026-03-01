<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Checklist (TIS section 5.2, 5.3) ─────────────────────────────
        Schema::create('checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')
                  ->constrained('tasks')
                  ->cascadeOnDelete();
            $table->string('title');
            $table->boolean('is_done')->default(false);
            $table->unsignedSmallInteger('order')->default(0);
            $table->foreignId('completed_by')
                  ->nullable()
                  ->constrained('employees')
                  ->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'order']);
        });

        // ── Attachments (TIS section 5.2) ─────────────────────────────────
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')
                  ->constrained('tasks')
                  ->cascadeOnDelete();
            $table->string('original_name');               // İstifadəçinin yüklədiyi fayl adı
            $table->string('stored_name');                 // Serverda saxlanılan ad (uuid)
            $table->string('disk')->default('local');      // Storage disk
            $table->string('path');                        // Fayl yolu
            $table->string('mime_type');
            $table->unsignedBigInteger('size');            // Byte ilə
            $table->foreignId('uploaded_by')
                  ->constrained('employees')
                  ->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('task_id');
        });

        // ── Comments (TIS section 5.4) ─────────────────────────────────────
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')
                  ->constrained('tasks')
                  ->cascadeOnDelete();
            $table->foreignId('employee_id')
                  ->constrained('employees')
                  ->restrictOnDelete();
            $table->text('body');
            $table->boolean('is_status_comment')->default(false); // Status dəyişikliyi şərhi
            $table->foreignId('parent_id')
                  ->nullable()                              // Cavab şərhlər
                  ->constrained('comments')
                  ->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('task_id');
        });

        // ── Status History (TIS section 5.4) ──────────────────────────────
        Schema::create('status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')
                  ->constrained('tasks')
                  ->cascadeOnDelete();

            // from/to status
            $table->enum('from_status', [
                'todo', 'in_progress', 'waiting_for_approve', 'completed', 'canceled',
            ])->nullable();
            $table->enum('to_status', [
                'todo', 'in_progress', 'waiting_for_approve', 'completed', 'canceled',
            ]);

            $table->foreignId('changed_by')
                  ->constrained('employees')
                  ->restrictOnDelete();
            $table->text('comment')->nullable();
            $table->timestamp('changed_at')->useCurrent();

            $table->index(['task_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_history');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('checklists');
    }
};
