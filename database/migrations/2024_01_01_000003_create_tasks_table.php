<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            // Əsas məlumat
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('space_id')
                  ->constrained('spaces')
                  ->restrictOnDelete();

            // Subtask dəstəyi (TIS: parent_task_id ilə)
            $table->foreignId('parent_task_id')
                  ->nullable()
                  ->constrained('tasks')
                  ->cascadeOnDelete();

            // Status (TIS section 5.2)
            $table->enum('status', [
                'todo',                 // Görüləcək
                'in_progress',          // İcra olunur
                'waiting_for_approve',  // Təsdiq gözləyir
                'completed',            // Tamamlandı
                'canceled',             // Ləğv olundu
            ])->default('todo');

            // Prioritet
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])
                  ->default('medium');

            // Tarixlər
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedSmallInteger('estimated_hours')->nullable(); // Təxmini icra müddəti (saat)

            // Görünürlük (TIS section 13)
            $table->enum('visibility', ['all_members', 'managers_only'])
                  ->default('all_members');

            // TIS xüsusi funksionallıqlar (section 4.1, 4.2)
            $table->boolean('require_approval')->default(false);   // "Təsdiq tələb olunur"
            $table->boolean('deadline_locked')->default(false);    // "Deadline yalnız assign edən dəyişdirə bilər"

            // Audit: Kim yaratdı vs kim assign etdi (TIS section 4.2)
            $table->foreignId('created_by')
                  ->constrained('employees')
                  ->restrictOnDelete();
            $table->foreignId('assigned_by')
                  ->nullable()                  // Referent tapşırıq daxil edərkən fərqli ola bilər
                  ->constrained('employees')
                  ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // İndekslər (performance üçün)
            $table->index(['space_id', 'status']);
            $table->index(['due_date', 'status']);
            $table->index('parent_task_id');
        });

        // Çox-çoxa: task ↔ assignees (TIS: "bir neçə əməkdaş")
        Schema::create('task_assignees', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('task_id')
                  ->constrained('tasks')
                  ->cascadeOnDelete();
            $table->foreignId('employee_id')
                  ->constrained('employees')
                  ->cascadeOnDelete();

            // Kim bu əməkdaşı assign etdi (ierarxik bölüşdürmə üçün)
            $table->foreignId('assigned_by')
                  ->nullable()
                  ->constrained('employees')
                  ->nullOnDelete();

            $table->timestamp('assigned_at')->useCurrent();

            $table->unique(['task_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_assignees');
        Schema::dropIfExists('tasks');
    }
};
