<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('surname');
            $table->string('patronymic')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('position')->nullable();        // Vəzifə
            $table->string('department')->nullable();      // Şöbə (HR-dan gələn info)
            $table->string('avatar')->nullable();

            // Employee directory inteqrasiyası üçün (bax: TIS section 10)
            $table->string('external_id')->nullable()->index(); // HR/LDAP/API-dən gələn ID
            $table->enum('source_type', ['local', 'hr_sync', 'ldap', 'phone_book'])
                  ->default('local');

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
