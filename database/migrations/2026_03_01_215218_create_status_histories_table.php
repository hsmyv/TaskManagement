<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::create('status_histories', function (Blueprint $table) {
        $table->id();
        $table->foreignId('task_id')->constrained()->onDelete('cascade');
        $table->string('from_status')->nullable();
        $table->string('to_status');
        $table->foreignId('changed_by')->constrained('employees')->onDelete('cascade');
        $table->string('comment')->nullable();
        $table->timestamp('changed_at');
    });
}

public function down()
{
    Schema::dropIfExists('status_histories');
}

};
