<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('space_members', function (Blueprint $table) {
            $table->boolean('is_manager')
                ->default(false)
                ->after('space_role');

            $table->index(['space_id', 'is_manager']);
        });
    }

    public function down(): void
    {
        Schema::table('space_members', function (Blueprint $table) {
            $table->dropIndex(['space_id', 'is_manager']);
            $table->dropColumn('is_manager');
        });
    }
};

