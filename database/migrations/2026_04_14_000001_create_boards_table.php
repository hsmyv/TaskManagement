<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boards', function (Blueprint $table) {
            $table->id();

            $table->foreignId('space_id')
                ->constrained('spaces')
                ->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            $table->foreignId('created_by')
                ->constrained('employees')
                ->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['space_id', 'deleted_at']);
        });

        Schema::create('board_members', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('board_id')
                ->constrained('boards')
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->timestamp('joined_at')->useCurrent();
            $table->foreignId('added_by')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            $table->unique(['board_id', 'employee_id']);
            $table->index(['employee_id', 'board_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_members');
        Schema::dropIfExists('boards');
    }
};

