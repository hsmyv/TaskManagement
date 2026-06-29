<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_helpers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('added_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('added_at')->useCurrent();
            $table->timestamps();

            $table->unique(['task_id', 'employee_id']);
            $table->index(['employee_id', 'task_id']);
        });

        Schema::create('task_supervisors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('added_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('added_at')->useCurrent();
            $table->timestamps();

            $table->unique(['task_id', 'employee_id']);
            $table->index(['employee_id', 'task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_supervisors');
        Schema::dropIfExists('task_helpers');
    }
};
