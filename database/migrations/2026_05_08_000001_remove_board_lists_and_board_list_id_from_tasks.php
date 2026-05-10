<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop FK/index/column from tasks first (depends on board_lists)
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'board_list_id')) {
                // Ensure board_id has its own index so FK doesn't depend on the composite index
                try {
                    $table->index(['board_id']);
                } catch (\Throwable $e) {
                    // ignore
                }

                try {
                    $table->dropConstrainedForeignId('board_list_id');
                } catch (\Throwable $e) {
                    // ignore
                }

                // Drop composite index if exists (must be after FK drop in MySQL)
                try {
                    $table->dropIndex('tasks_board_id_board_list_id_board_position_index');
                } catch (\Throwable $e) {
                    // ignore
                }

                $table->dropColumn('board_list_id');
            }

            // Recreate a simpler index for board ordering
            try {
                $table->index(['board_id', 'board_position']);
            } catch (\Throwable $e) {
                // ignore
            }
        });

        Schema::dropIfExists('board_lists');
    }

    public function down(): void
    {
        // Recreate board_lists and board_list_id (best-effort rollback)
        Schema::create('board_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('boards')->cascadeOnDelete();
            $table->string('title');
            $table->enum('type', ['todo', 'in_progress', 'done', 'rejected', 'custom'])->default('custom');
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('created_by')->constrained('employees')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['board_id', 'position']);
            $table->index(['board_id', 'deleted_at']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'board_list_id')) {
                $table->foreignId('board_list_id')
                    ->nullable()
                    ->after('board_id')
                    ->constrained('board_lists')
                    ->nullOnDelete();
            }

            // Restore original index (drop new one if present)
            try {
                $table->dropIndex(['board_id', 'board_position']);
            } catch (\Throwable $e) {
                // ignore
            }

            try {
                $table->index(['board_id', 'board_list_id', 'board_position']);
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
};

