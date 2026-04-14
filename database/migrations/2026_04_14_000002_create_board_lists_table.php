<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_lists', function (Blueprint $table) {
            $table->id();

            $table->foreignId('board_id')
                ->constrained('boards')
                ->cascadeOnDelete();

            $table->string('title');

            // Used for dashboard groupings + default columns (optional)
            $table->enum('type', ['todo', 'in_progress', 'done', 'rejected', 'custom'])
                ->default('custom');

            $table->unsignedInteger('position')->default(0);

            $table->foreignId('created_by')
                ->constrained('employees')
                ->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['board_id', 'position']);
            $table->index(['board_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_lists');
    }
};

