<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('board_id')
                ->nullable()
                ->after('space_id')
                ->constrained('boards')
                ->nullOnDelete();

            $table->foreignId('board_list_id')
                ->nullable()
                ->after('board_id')
                ->constrained('board_lists')
                ->nullOnDelete();

            $table->unsignedInteger('board_position')
                ->default(0)
                ->after('board_list_id');

            $table->index(['board_id', 'board_list_id', 'board_position']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['board_id', 'board_list_id', 'board_position']);
            $table->dropConstrainedForeignId('board_list_id');
            $table->dropConstrainedForeignId('board_id');
            $table->dropColumn('board_position');
        });
    }
};

