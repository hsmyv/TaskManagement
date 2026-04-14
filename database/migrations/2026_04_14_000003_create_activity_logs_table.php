<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('space_id')
                ->nullable()
                ->constrained('spaces')
                ->nullOnDelete();

            $table->foreignId('board_id')
                ->nullable()
                ->constrained('boards')
                ->nullOnDelete();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->string('action');       // create|update|delete|move
            $table->string('entity_type');  // board_list|task|board
            $table->unsignedBigInteger('entity_id');
            $table->json('meta')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['board_id', 'created_at']);
            $table->index(['space_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};

