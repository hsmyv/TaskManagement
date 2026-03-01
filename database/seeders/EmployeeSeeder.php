<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Space;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // ── Administrator ─────────────────────────────────────────────────
        $admin = Employee::firstOrCreate(
            ['email' => 'admin@tis.local'],
            [
                'name'     => 'Sistem',
                'surname'  => 'Administratoru',
                'password' => Hash::make('admin123!'),
                'position' => 'IT Administrator',
                'is_active' => true,
            ]
        );
        $admin->assignRole(UserRole::Administrator->value);

        // ── Executive Manager ─────────────────────────────────────────────
        $exec = Employee::firstOrCreate(
            ['email' => 'exec@tis.local'],
            [
                'name'     => 'Əli',
                'surname'  => 'Əliyev',
                'password' => Hash::make('exec123!'),
                'position' => 'İdarə Heyəti Üzvü',
                'is_active' => true,
            ]
        );
        $exec->assignRole(UserRole::ExecutiveManager->value);

        // ── Test Space ────────────────────────────────────────────────────
        $space = Space::firstOrCreate(
            ['slug' => 'iried'],
            [
                'name'        => 'İRİED',
                'description' => 'İnformasiya Resursları və İnnovasiya Departamenti',
                'color'       => '#3B82F6',
                'is_active'   => true,
                'created_by'  => $admin->id,
            ]
        );

        // ── Senior Manager ────────────────────────────────────────────────
        $senior = Employee::firstOrCreate(
            ['email' => 'senior@tis.local'],
            [
                'name'     => 'Nigar',
                'surname'  => 'Hüseynova',
                'password' => Hash::make('senior123!'),
                'position' => 'Departament Müdiri',
                'is_active' => true,
            ]
        );
        $senior->assignRole(UserRole::SeniorManager->value);

        // Space-ə əlavə et
        $space->members()->syncWithoutDetaching([
            $senior->id => ['space_role' => 'senior_manager', 'added_by' => $admin->id],
        ]);

        // ── Middle Manager ────────────────────────────────────────────────
        $middle = Employee::firstOrCreate(
            ['email' => 'middle@tis.local'],
            [
                'name'     => 'Kənan',
                'surname'  => 'Məmmədov',
                'password' => Hash::make('middle123!'),
                'position' => 'Şöbə Müdiri',
                'is_active' => true,
            ]
        );
        $middle->assignRole(UserRole::MiddleManager->value);
        $space->members()->syncWithoutDetaching([
            $middle->id => ['space_role' => 'middle_manager', 'added_by' => $senior->id],
        ]);

        // ── Employee ──────────────────────────────────────────────────────
        $emp = Employee::firstOrCreate(
            ['email' => 'emp@tis.local'],
            [
                'name'     => 'Aysel',
                'surname'  => 'Rəsulova',
                'password' => Hash::make('emp123!'),
                'position' => 'Mütəxəssis',
                'is_active' => true,
            ]
        );
        $emp->assignRole(UserRole::Employee->value);
        $space->members()->syncWithoutDetaching([
            $emp->id => ['space_role' => 'employee', 'added_by' => $senior->id],
        ]);

        $this->command->info('✅ Test istifadəçiləri yaradıldı:');
        $this->command->table(
            ['E-poçt', 'Rol', 'Şifrə'],
            [
                ['admin@tis.local',  'Administrator',    'admin123!'],
                ['exec@tis.local',   'Executive Manager','exec123!'],
                ['senior@tis.local', 'Senior Manager',   'senior123!'],
                ['middle@tis.local', 'Middle Manager',   'middle123!'],
                ['emp@tis.local',    'Employee',         'emp123!'],
            ]
        );
    }
}
