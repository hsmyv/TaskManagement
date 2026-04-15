<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('space_members', function (Blueprint $table) {
            $table->boolean('can_create_boards')
                ->default(false)
                ->after('is_manager');

            $table->index(['space_id', 'can_create_boards']);
        });
    }

    public function down(): void
    {
        Schema::table('space_members', function (Blueprint $table) {
            $table->dropIndex(['space_id', 'can_create_boards']);
            $table->dropColumn('can_create_boards');
        });
    }
};

