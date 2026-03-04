<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            [
                'name'        => 'İnzibati və Hüquqi İşlər Departamenti',
                'code'        => 'İHİD',
                'description' => null,
                'is_active'   => true,
            ],
            [
                'name'        => 'Maliyyə və Mühasibat Departamenti',
                'code'        => 'MMD',
                'description' => null,
                'is_active'   => true,
            ],
            [
                'name'        => 'İnsan Resursları Departamenti',
                'code'        => 'İRD',
                'description' => null,
                'is_active'   => true,
            ],
            [
                'name'        => 'İnformasiya Texnologiyaları Departamenti',
                'code'        => 'İTD',
                'description' => null,
                'is_active'   => true,
            ],
            [
                'name'        => 'Strateji İnkişaf və Planlaşdırma Departamenti',
                'code'        => 'SİPD',
                'description' => null,
                'is_active'   => true,
            ],
            [
                'name'        => 'Dövlət Satınalmaları Departamenti',
                'code'        => 'DSD',
                'description' => null,
                'is_active'   => true,
            ],
            [
                'name'        => 'Beynəlxalq Əlaqələr Departamenti',
                'code'        => 'BƏD',
                'description' => null,
                'is_active'   => true,
            ],
            [
                'name'        => 'Audit və Daxili Nəzarət Departamenti',
                'code'        => 'ADN',
                'description' => null,
                'is_active'   => true,
            ],
            [
                'name'        => 'İctimaiyyətlə Əlaqələr Departamenti',
                'code'        => 'İƏD',
                'description' => null,
                'is_active'   => true,
            ],
            [
                'name'        => 'Arxiv və Sənədləşmə Departamenti',
                'code'        => 'ASD',
                'description' => null,
                'is_active'   => true,
            ],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(
                ['code' => $dept['code']],
                $dept
            );
        }

        $this->command->info('✅ ' . count($departments) . ' departament əlavə edildi.');
    }
}
