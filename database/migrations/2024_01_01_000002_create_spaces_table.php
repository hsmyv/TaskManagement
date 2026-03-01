<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();             // URL-friendly ad
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#3B82F6'); // UI üçün rəng
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')
                  ->constrained('employees')
                  ->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // Space üzvlüyü + daxili rol
        Schema::create('space_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')
                  ->constrained('spaces')
                  ->cascadeOnDelete();
            $table->foreignId('employee_id')
                  ->constrained('employees')
                  ->cascadeOnDelete();

            // Space daxili rol: senior_manager, middle_manager, employee
            // (qlobal roller Spatie ilə idarə olunur)
            $table->enum('space_role', ['senior_manager', 'middle_manager', 'employee'])
                  ->default('employee');

            $table->timestamp('joined_at')->useCurrent();
            $table->foreignId('added_by')
                  ->nullable()
                  ->constrained('employees')
                  ->nullOnDelete();

            $table->unique(['space_id', 'employee_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('space_members');
        Schema::dropIfExists('spaces');
    }
};
