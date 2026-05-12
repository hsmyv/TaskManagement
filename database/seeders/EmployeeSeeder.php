<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Board;
use App\Models\Employee;
use App\Models\Space;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Employee::firstOrCreate(
            ['email' => 'admin@tis.local'],
            [
                'name'      => 'Sistem',
                'surname'   => 'Administratoru',
                'password'  => Hash::make('admin123!'),
                'position'  => 'IT Administrator',
                'is_active' => true,
            ]
        );
        $admin->assignRole(UserRole::Administrator->value);

        $executivePeople = [
            ['name' => 'Fərid', 'surname' => 'Məmmədov', 'position' => 'İdarə Heyətinin sədri'],
            ['name' => 'İdarə Heyəti', 'surname' => 'sədrinin müavini 1', 'position' => 'İdarə Heyəti sədrinin müavini'],
            ['name' => 'İdarə Heyəti', 'surname' => 'sədrinin müavini 2', 'position' => 'İdarə Heyəti sədrinin müavini'],
            ['name' => 'İdarə Heyəti', 'surname' => 'sədrinin müavini 3', 'position' => 'İdarə Heyəti sədrinin müavini'],
            ['name' => 'İdarə Heyəti', 'surname' => 'sədrinin müavini 4', 'position' => 'İdarə Heyəti sədrinin müavini'],
            ['name' => 'İdarə Heyəti', 'surname' => 'sədrinin müşaviri 1', 'position' => 'İdarə Heyəti sədrinin müşaviri'],
            ['name' => 'İdarə Heyəti', 'surname' => 'sədrinin müşaviri 2', 'position' => 'İdarə Heyəti sədrinin müşaviri'],
        ];

        foreach ($executivePeople as $index => $person) {
            $exec = Employee::firstOrCreate(
                ['email' => 'exec' . ($index + 1) . '@tis.local'],
                [
                    'name'      => $person['name'],
                    'surname'   => $person['surname'],
                    'password'  => Hash::make('exec123!'),
                    'position'  => $person['position'],
                    'is_active' => true,
                ]
            );

            $exec->assignRole(UserRole::ExecutiveManager->value);
        }

        $spaceNames = [
            'Maliyyə və mühasibatlıq departamenti',
            'Satınalmalar və təminat departamenti',
            'İnsan resurslarının idarə edilməsi departamenti',
            'Beynəlxalq əlaqələr və layihələr departamenti',
            'Strategiya və məlumatların idarə edilməsi departamenti',
            'Xidmətlər departamenti',
            'İctimaiyyətlə əlaqələr və kommunikasiya departamenti',
            'Hüquq, kargüzarlığın təşkili və inzibati idarəetmə departamenti',
            'Keyfiyyətin idarə edilməsi və xidmətlərin monitorinqi departamenti',
            'DOST İş Mərkəzi',
            'DOST İnklüziv İnkişaf və Yaradıcılıq Mərkəzi',
            'Rəqəmsal İnnovasiyalar Mərkəzi',
            'DOST İnfrastruktur Mərkəzi',
            'DOST Çağrı Mərkəzi 142',
        ];

        $boardNamePool = [
            'Planlama və hesabatlılıq şöbəsi',
            'Təhlil və monitorinq şöbəsi',
            'Əməliyyatların koordinasiyası şöbəsi',
            'Sənədləşmə və nəzarət şöbəsi',
            'Layihələrin idarə olunması şöbəsi',
            'Məlumatların emalı şöbəsi',
            'Daxili nəzarət şöbəsi',
            'İcra intizamı şöbəsi',
            'Resursların planlaşdırılması şöbəsi',
            'Əlaqələndirmə və dəstək şöbəsi',
        ];

        $memberFirstNames = ['Aysel', 'Nərmin', 'Ləman', 'Tural', 'Elvin', 'Murad', 'Fidan', 'Kənan', 'Nigar', 'Rauf'];
        $memberLastNames = ['Məmmədov', 'Hüseynov', 'Rəsulova', 'Quliyev', 'Əliyeva', 'İsmayılov', 'Səfərova', 'Cəfərov'];

        foreach ($spaceNames as $spaceIndex => $spaceName) {
            $slug = Str::slug($spaceName);

            $space = Space::firstOrCreate(
                ['slug' => $slug],
                [
                    'name'                => $spaceName,
                    'description'         => $spaceName . ' üzrə iş axınının idarə olunması',
                    'color'               => collect(['#3B82F6', '#8B5CF6', '#10B981', '#F59E0B', '#EF4444'])->random(),
                    'is_active'           => true,
                    'created_by'          => $admin->id,
                    'manager_employee_id' => null,
                ]
            );

            $senior = Employee::firstOrCreate(
                ['email' => 'senior' . ($spaceIndex + 1) . '@tis.local'],
                [
                    'name'      => 'Müdir' . ($spaceIndex + 1),
                    'surname'   => 'Departament',
                    'password'  => Hash::make('senior123!'),
                    'position'  => 'Departament müdiri',
                    'is_active' => true,
                ]
            );
            $senior->assignRole(UserRole::SeniorManager->value);

            $space->update([
                'manager_employee_id' => $senior->id,
            ]);

            $space->members()->syncWithoutDetaching([
                $senior->id => [
                    'space_role' => 'senior_manager',
                    'is_manager' => true,
                    'added_by'   => $admin->id,
                ],
            ]);

            $selectedBoardNames = collect($boardNamePool)->shuffle()->take(rand(3, 5))->values();

            foreach ($selectedBoardNames as $boardIndex => $boardName) {
                $board = Board::firstOrCreate(
                    [
                        'space_id' => $space->id,
                        'name'     => $boardName,
                    ],
                    [
                        'description' => $boardName . ' üzrə tapşırıqlar',
                        'created_by'  => $senior->id,
                    ]
                );

                $middle = Employee::firstOrCreate(
                    ['email' => 'middle' . $space->id . '_' . ($boardIndex + 1) . '@tis.local'],
                    [
                        'name'      => 'Şöbə' . ($boardIndex + 1),
                        'surname'   => 'Müdiri',
                        'password'  => Hash::make('middle123!'),
                        'position'  => 'Şöbə müdiri',
                        'is_active' => true,
                    ]
                );
                $middle->assignRole(UserRole::MiddleManager->value);

                $space->members()->syncWithoutDetaching([
                    $middle->id => [
                        'space_role' => 'middle_manager',
                        'is_manager' => false,
                        'added_by'   => $senior->id,
                    ],
                ]);
            }

            for ($i = 1; $i <= 6; $i++) {
                $name = $memberFirstNames[array_rand($memberFirstNames)];
                $surname = $memberLastNames[array_rand($memberLastNames)];

                $employee = Employee::firstOrCreate(
                    ['email' => 'emp' . $space->id . '_' . $i . '@tis.local'],
                    [
                        'name'      => $name,
                        'surname'   => $surname,
                        'password'  => Hash::make('emp123!'),
                        'position'  => collect([
                            'Baş mütəxəssis',
                            'Mütəxəssis',
                            'Aparıcı mütəxəssis',
                            'Koordinator',
                            'Analitik',
                        ])->random(),
                        'is_active' => true,
                    ]
                );

                $employee->assignRole(UserRole::Employee->value);

                $space->members()->syncWithoutDetaching([
                    $employee->id => [
                        'space_role' => 'employee',
                        'is_manager' => false,
                        'added_by'   => $senior->id,
                    ],
                ]);
            }
        }

        $this->command->info('✅ Seed tamamlandı');
        $this->command->table(
            ['E-poçt', 'Rol', 'Şifrə'],
            [
                ['admin@tis.local', 'Administrator', 'admin123!'],
                ['exec1@tis.local', 'Executive Manager', 'exec123!'],
                ['senior1@tis.local', 'Senior Manager', 'senior123!'],
                ['middle1_1@tis.local', 'Middle Manager', 'middle123!'],
                ['emp1_1@tis.local', 'Employee', 'emp123!'],
            ]
        );
    }
}
