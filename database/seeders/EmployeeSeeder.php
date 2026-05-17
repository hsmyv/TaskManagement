<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Board;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Space;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'password123!';

    private const DIRECTORY_TEXT = <<<'TXT'
Fərid Məmmədov
İdarə Heyətinin sədri

Seymur Alıyev
İdarə Heyəti sədrinin müavini

Aygün Musayeva
İdarə Heyəti sədrinin müavini

Elnur Əliyev
İdarə Heyəti sədrinin müavini

Arif Ataxanlı
İdarə Heyəti sədrinin müavini

Müşavirlər

Nazilə Həşim zadə
İdarə Heyəti sədrinin müşaviri

Tural Güləhmədov
İdarə Heyəti sədrinin müşaviri







Maliyyə və mühasibatlıq departamenti

Rəhbərlik

Şamxal İbrahimov
Departament müdiri

Nərminə Kamal
Departament müdirinin müavini

Nicat Məmmədov
Departament müdirinin müavini - baş mühasib

Dəniz Abasova
Referent
Əmək haqqı şöbəsi

Coşqun Əsgərli


Əmək haqqı şöbəsinin müdiri

Kamran Əsədov
Baş mütəxəssis

Əmrah Kərimli
Baş mütəxəssis

Türkan Malik
Aparıcı mütəxəssis

Seymur Sadıqov
Mütəxəssis
Aktivlərin uçotu şöbəsi

Sərxan Əsgərov


Aktivlərin uçotu şöbəsinin müdiri

Rəhim Abdinov
Böyük mütəxəssis

Rəşad Qarayev
Aparıcı mütəxəssis

Natiq Muradov
Mütəxəssis


Hesabatlılıq və təhlil şöbəsi

Samir İmanov
Böyük mütəxəssis

Nurlan Abdullazadə
Böyük mütəxəssis

Əyyub Abdullayev
Mütəxəssis

Çinarə Məmmədli
Mütəxəssis
Maliyyə və büdcə şöbəsi

Ayşən Bağırova
Maliyyə və büdcə şöbəsinin müdiri

Təbriz Novruzlu
Baş mütəxəssis

Nicat Məmmədov
Böyük mütəxəssis

Sədaqət Abdinova
Aparıcı mütəxəssis

Aytən Mustafalı
Aparıcı mütəxəssis

Elvira Abbasova
Mütəxəssis

Dilarə Aydəmirova
Referent







Satınalmalar və təminat departamenti

Rəhbərlik

Fariz Qayıbov
Departament müdirinin müavini

Qəşəng Cəfərli-İbrahimzadə
Referent
Satınalmalar şöbəsi

Aydın Məmmədov
Satınalmalar şöbəsinin müdiri

Aqil Ağamirzə
Baş mütəxəssis

Hamlet Nağızadə
Mütəxəssis

Vüsal Bayramov
Baş mütəxəssis

Sudabə Qocayeva Hümbətli
Böyük mütəxəssis
Mərkəzi təminat şöbəsi

Zahid Hacıbəyli
Mərkəzi təminat şöbəsinin müdiri

Toğrul Allahyarov
Baş mütəxəssis

Zivərxanım Şaxtaxtinskaya
Aparıcı mütəxəssis

Elvin Qədirov
Mütəxəssis

Güllü Mirabdullayeva
Mütəxəssis

Oktay Nəcəfli
Mütəxəssis

Tehran Niftəliyev
Mütəxəssis

Hüseyn Yusifov
Aparıcı mütəxəssis

Elməddin Hüseynzadə
Mütəxəssis
Təsərrüfat şöbəsi

Elşən Əliyev
Təsərrüfat şöbəsinin müdiri

Səid Həsənov
Mütəxəssis

Samir Rzayev
Baş inzibatçı

Eyvaz Eyvazzadə
İnzibatçı

Nizami Məmmədov
Böyük mütəxəssis

Emil Yaqubov
Sürücü

Saleh Əmrahov
İnzibatçı

Yusif Əhmədzadə
İnzibatçı

Xalıq Əsədov
Sürücü

Tahir Quliyev
Sürücü

Xəqani Tağıyev
Baş inzibatçı

Nurlan Şəfayətli
İnzibatçı

Mehman Haqverdiyev
Sürücü

Məshəti Mehtiyeva
Xidmətçi

Sevinc Rəhimova
Xidmətçi

Mətanət Ələsgərli
Xidmətçi

Ceyhun Kuliyev
Baş inzibatçı

İlhamə Quliyeva
Xidmətçi

Ruhəngiz Qafarova
Xidmətçi

Elçin Nağıyev
Sürücü

İlkin Kərimov
Sürücü

Sürayyə Əzizova
Mütəxəssis

Cahangir Ağa Dəmirov
Sürücü







İnsan resurslarının idarə edilməsi departamenti

Rəhbərlik

Leyla Hüseynova
Departament müdiri

Tural Əlizadə
Departament müdirinin müavini

Nərmin İbadova
Referent
İnsan resurslarının planlaşdırılması və işə qəbul şöbəsi

Ərşad Hacıbəyli
İnsan resurslarının planlaşdırılması və işə qəbul şöbəsinin müdiri

Ayşən Şirinova
Böyük mütəxəssis

Zəhra Qəhrəmanova
Böyük mütəxəssis
Əmək münasibətlərinin tənzimlənməsi şöbəsi

Nüsrət Xəlilov
Əmək münasibətlərinin tənzimlənməsi şöbəsinin müdiri

Vüsalə Kərimli
Böyük mütəxəssis

Ləman Əmirli
Aparıcı mütəxəssis

Asiman Əhmədov
Aparıcı mütəxəssis
Fəaliyyətin qiymətləndirilməsi və heyətin inkişafı şöbəsi

Dilbər Heydərova
Baş mütəxəssis

Rifat Alməmmədov
Aparıcı mütəxəssis
Könüllülərlə iş şöbəsi

Sona Sadıxlı
Böyük mütəxəssis

Seymur İbrahimov
Aparıcı mütəxəssis

Məleykə Əlili
Mütəxəssis

Amil Ağazadə
Mütəxəssis

Ayan Hacızadə
Referent




Beynəlxalq əlaqələr və layihələr departamenti

Rəhbərlik
Heç kim yoxdur - space müdiri boş olacaq.

Zemfira Dadaşova
Referent
Marketinq şöbəsi

Aytən Alməmmədova
Aparıcı mütəxəssis

Bəşirbəy Qallacov
Aparıcı mütəxəssis

Əziz Qasımov
Referent
Layihələr şöbəsi

Fidan Bəhramzadə
Baş mütəxəssis

Ləman Əmirova
Mütəxəssis
Beynəlxalq əlaqələr şöbəsi

Kənan Fərazi
Beynəlxalq əlaqələr şöbəsinin müdiri

Nərgiz Zülfiyeva
Aparıcı mütəxəssis

Məryam Mayılova
Aparıcı mütəxəssis

Nərmin Budaqlı
Mütəxəssis

Nərmin Ərşadzadə
Mütəxəssis
Protokol şöbəsi

Rəna Cəfərova
Mütəxəssis





Strategiya və məlumatların idarə edilməsi departamenti

Rəhbərlik

Şahin Zülfüqarov
Departament müdiri

Gülsüm Baxışova
Referent
Strategiya şöbəsi

Babək Mahmudov
Strategiya şöbəsinin müdiri

Zülfiyyə Nağıyeva
Baş mütəxəssis

Zəhra Haqverdiyeva
Böyük mütəxəssis
Məlumatların idarə edilməsi şöbəsi

Səidə Məmmədli
Məlumatların idarə edilməsi şöbəsinin müdiri

Qəşəng Abbasova
Böyük mütəxəssis

Zərifəxanım Quliyeva
Aparıcı mütəxəssis

Aytac Əzimzadə
Aparıcı mütəxəssis

Fidan Əzizova
Mütəxəssis

Günel Əliyeva
Mütəxəssis

Natiq Seyidov
Mütəxəssis

Aygün Məmmədova
Mütəxəssis

Zeynəb Süleymanova
Mütəxəssis








Xidmətlər departamenti

Rəhbərlik

Gündüz Mehdiyev
Departament müdiri

Nərmin Axundova
Departament müdirinin müavini

Nərmin Əliyeva
Referent
Əmək və məşğulluq xidmətlərinin təşkili şöbəsi

Tural Əsgərov
Əmək və məşğulluq xidmətlərinin təşkili şöbəsinin müdiri

Gülçöhrə Bayramlı
Baş mütəxəssis

Məhəmməd Xəlilov
Böyük mütəxəssis

Afət Qarayeva
Aparıcı mütəxəssis

Tünzalə Hüseynova
Baş mütəxəssis
Sosial müdafiə üzrə xidmətlərin təşkili şöbəsi

Cabir Cabbarlı
Sosial müdafiə üzrə xidmətlərin təşkili şöbəsinin müdiri

Əziz Məmmədli
Baş mütəxəssis

İlkin Məmmədrzayev
Baş mütəxəssis

Nail Məmmədov
Böyük mütəxəssis

Calal Sultanlı
Böyük mütəxəssis

Elnur Məmmədov
Aparıcı mütəxəssis
Səyyar və sosial xidmətlər şöbəsi

Şəhla Qəhrəmanova
Səyyar və sosial xidmətlər şöbəsinin müdiri

Məhəmməd Bayramlı
Böyük mütəxəssis

Günəş Şirvanzadə
Aparıcı mütəxəssis

Ülvi Məmmədli
Mütəxəssis

Nurlan Əzizli
Mütəxəssis








İctimaiyyətlə əlaqələr və kommunikasiya departamenti


Rəhbərlik

Şahin Əliyev
Departament müdiri

Teymur Hacıyev
Departament müdirinin müavini

Amid Həsənquliyev
Referent
Kommunikasiya şöbəsi

İlqar Əhmədov
Kommunikasiya şöbəsinin müdiri

Aygün Talıbova
Böyük mütəxəssis

Yusif Ağayev
Böyük mütəxəssis

Naib Əhmədov
Böyük mütəxəssis

Nigar Quliyeva
Aparıcı mütəxəssis

Zeynəb Əsədova
Aparıcı mütəxəssis (dizayner)

Orxan Həzrətquliyev
Mütəxəssis

Lalə İsgəndər
Mütəxəssis

Ədilə Fərzəliyeva
Mütəxəssis

Əlipaşa Novruzov
Dizayner

Kənan Baqəliyev
İnzibatçı
İnformasiya şöbəsi

Etibar Tağıyev
İnformasiya şöbəsinin müdiri

Fərid Qarazadə
Aparıcı mütəxəssis

Ləman Dadaşzadə
Aparıcı mütəxəssis (qrafik dizayner)

Mirzə Ələkbərov
Mütəxəssis (fotoqraf/videoqraf)

Günel Mehdiyeva
Dizayner
Rəqəmsal media studiyası

Nazim Şərbətov
Rəqəmsal media studiyasının rəhbəri

Sevinc Süleymanova
Aparıcı mütəxəssis

İlkin Səmədov
Mütəxəssis (fotoqraf/videoqraf)

Rasim Amanov
Mütəxəssis (informasiya üzrə)

Turan Qədimli
Mütəxəssis

Urfan Əliyev
Baş inzibatçı

İsmayıl İsmayılov
Baş inzibatçı

Mehri Qurbanova
İnzibatçı






Hüquq, kargüzarlığın təşkili və inzibati idarəetmə departamenti

Rəhbərlik

İlkin Zərbəliyev
Departament müdiri

Aydan Salıfova
Departament müdirinin müavini

Tavad Mustafayeva
Departament müdirinin müavini

Zahidə Fərəməzova
Referent

Leyla Mirzəyeva
Referent

Fidan Əliyeva
Referent

Elmira Quliyeva
Referent
Hüquq şöbəsi

Həcər Əmirova
Baş mütəxəssis

Aytən Süleymanlı
Mütəxəssis
Vətəndaş qəbulu və sənədlərlə iş şöbəsi

Şəhla Məmmədli
Vətəndaş qəbulu və sənədlərlə iş şöbəsinin müdiri

Pərvanə Dadaşova
Baş mütəxəssis

Könül Abbasova
Böyük mütəxəssis

Aynur Abbasova
Böyük mütəxəssis

Nəzifə Mustafayeva
Böyük mütəxəssis

Fatma Abbasova
Aparıcı mütəxəssis

Fidan Musayeva
Aparıcı mütəxəssis

Fatimə Hüseynzadə
Aparıcı mütəxəssis

İlyas İlyasov
Mütəxəssis
Təhlükəsizlik şöbəsi

Natiq Nəcəfzadə
Təhlükəsizlik şöbəsinin müdiri

Sənan Məmməd-zadə
Aparıcı mütəxəssis

Anar Məmmədov
Mütəxəssis

Sərxan Veysəlli
Baş təlimatçı

Nahid Əkbərov
Baş təlimatçı
Daxili araşdırmalar şöbəsi

İbrahim Aslanlı
Daxili araşdırmalar şöbəsinin müdiri

Ağalar Qarayev
Baş mütəxəssis

Emin Həsənov
Böyük mütəxəssis

Azər Hüseynli
Aparıcı mütəxəssis

Nərmin Ramazanlı
Aparıcı mütəxəssis






Keyfiyyətin idarə edilməsi və xidmətlərin monitorinqi departamenti

Rəhbərlik

Elçin Həsənov
Departament müdiri

Natiq Qaçayev
Departament müdirinin müavini

Şahin Məmmədov
Referent
Koordinatorlar

Nigar Quliyeva
Keyfiyyətin idarə edilməsi üzrə koordinator

İlqar Quliyev
SƏTƏM üzrə koordinator

Roman Kurkin
Rəqəmsal informasiya resurslarının idarə edilməsi üzrə koordinator


Xidmətlərin monitorinqi şöbəsi

Şəbnəm Məmmədova
Xidmətlərin monitorinqi şöbəsinin müdiri

Tural Niftəliyev
Baş mütəxəssis

Musa Mehdiyev
Baş mütəxəssis

Sevinc Xəlilova
Böyük mütəxəssis

Həlimət Həsənzadə
Böyük mütəxəssis

Nailə Həsənova
Aparıcı mütəxəssis

Ceyhun Hüseynli
Aparıcı mütəxəssis

Əsli Xəlilova
Aparıcı mütəxəssis

İlkin Əlizadə
Aparıcı mütəxəssis

Muraz Qanbaylı
Mütəxəssis

Həsən Musayev
Mütəxəssis


Ayrıca space yarat buna:
Audit şöbəsi
Rəhbərlik
yoxdur 

Emil Məmmədov
Aparıcı mütəxəssis
TXT;

    public function run(): void
    {
        $admin = Employee::updateOrCreate(
            ['email' => 'admin@tis.local'],
            [
                'name' => 'Sistem',
                'surname' => 'Administratoru',
                'password' => Hash::make('admin123!'),
                'position' => 'IT Administrator',
                'source_type' => 'local',
                'is_active' => true,
            ]
        );
        $admin->assignRole(UserRole::Administrator->value);

        $aiEmployee = Employee::updateOrCreate(
            ['email' => 'ai@tis.local'],
            [
                'name' => 'AI',
                'surname' => '',
                'password' => Hash::make(self::DEFAULT_PASSWORD),
                'position' => 'AI köməkçi',
                'source_type' => 'local',
                'is_active' => true,
            ]
        );
        $aiEmployee->assignRole(UserRole::Employee->value);

        $parsed = $this->parseDirectory();
        $emailCounts = [];
        $createdEmployees = 1;

        foreach ($parsed['executives'] as $person) {
            $employee = $this->upsertEmployee($person, null, $emailCounts, self::DEFAULT_PASSWORD);
            $employee->assignRole(UserRole::ExecutiveManager->value);
            $createdEmployees++;
        }

        foreach ($parsed['departments'] as $departmentIndex => $departmentData) {
            $department = Department::updateOrCreate(
                ['name' => $departmentData['name']],
                [
                    'code' => $this->departmentCode($departmentData['name'], $departmentIndex + 1),
                    'description' => null,
                    'is_active' => true,
                ]
            );

            $space = Space::updateOrCreate(
                ['slug' => Str::slug($departmentData['name'])],
                [
                    'name' => $departmentData['name'],
                    'description' => $departmentData['name'] . ' üzrə iş axınının idarə olunması',
                    'color' => $this->spaceColor($departmentIndex),
                    'is_active' => true,
                    'department_id' => $department->id,
                    'created_by' => $admin->id,
                ]
            );

            $spaceEmployees = [];
            foreach ($departmentData['members'] as $person) {
                $employee = $this->upsertEmployee($person, $department->id, $emailCounts, self::DEFAULT_PASSWORD);
                $employee->assignRole($this->globalRoleFor($person['role']));

                $spaceRole = $this->spaceRoleFor($person['role']);
                $isManager = $person['role'] === 'department_manager';
                $canCreateBoards = in_array($person['role'], ['department_manager', 'department_deputy', 'section_manager', 'coordinator'], true);

                $space->members()->syncWithoutDetaching([
                    $employee->id => [
                        'space_role' => $spaceRole,
                        'is_manager' => $isManager,
                        'can_create_boards' => $canCreateBoards,
                        'added_by' => $admin->id,
                    ],
                ]);

                if ($isManager && !$space->manager_employee_id) {
                    $space->update(['manager_employee_id' => $employee->id]);
                }

                $spaceEmployees[$person['key']] = $employee;
                $createdEmployees++;
            }

            $space->members()->syncWithoutDetaching([
                $aiEmployee->id => [
                    'space_role' => 'employee',
                    'is_manager' => false,
                    'can_create_boards' => false,
                    'added_by' => $admin->id,
                ],
            ]);

            if (!$departmentData['manager']) {
                $space->update(['manager_employee_id' => null]);
            }

            foreach ($departmentData['boards'] as $boardData) {
                $boardCreator = $this->boardCreator($boardData, $departmentData['members'], $spaceEmployees, $space, $admin);

                $board = Board::updateOrCreate(
                    [
                        'space_id' => $space->id,
                        'name' => $boardData['name'],
                    ],
                    [
                        'description' => $boardData['name'] . ' üzrə tapşırıqlar',
                        'created_by' => $boardCreator->id,
                    ]
                );

                foreach ($boardData['member_keys'] as $memberKey) {
                    if (!isset($spaceEmployees[$memberKey])) {
                        continue;
                    }

                    $board->members()->syncWithoutDetaching([
                        $spaceEmployees[$memberKey]->id => [
                            'added_by' => $boardCreator->id,
                        ],
                    ]);
                }

                $board->members()->syncWithoutDetaching([
                    $aiEmployee->id => [
                        'added_by' => $boardCreator->id,
                    ],
                ]);
            }
        }

        $this->command?->info('İşçilər.txt əsasında seeding tamamlandı.');
        $this->command?->table(
            ['Hesab', 'Şifrə'],
            [
                ['admin@tis.local', 'admin123!'],
                ['digər bütün seed işçiləri', self::DEFAULT_PASSWORD],
            ]
        );
    }

    private function upsertEmployee(array $person, ?int $departmentId, array &$emailCounts, string $password): Employee
    {
        return Employee::updateOrCreate(
            ['email' => $this->emailFor($person['full_name'], $emailCounts)],
            [
                'name' => $person['name'],
                'surname' => $person['surname'],
                'password' => Hash::make($password),
                'position' => $person['position'],
                'department_id' => $departmentId,
                'source_type' => 'local',
                'is_active' => true,
            ]
        );
    }

    private function parseDirectory(): array
    {
        $lines = array_values(array_filter(
            array_map('trim', preg_split('/\R/u', self::DIRECTORY_TEXT)),
            fn (string $line) => $line !== ''
        ));

        $departmentHeadings = $this->departmentHeadings();
        $executives = [];
        $departments = [];
        $i = 0;
        $personIndex = 1;

        while ($i < count($lines) && !in_array($lines[$i], $departmentHeadings, true) && !Str::startsWith($lines[$i], 'Ayrıca space')) {
            if ($this->isSectionMarker($lines[$i])) {
                $i++;
                continue;
            }

            $name = $lines[$i++] ?? null;
            $position = $lines[$i++] ?? null;

            if ($name && $position && $this->looksLikePosition($position)) {
                $executives[] = $this->person($name, $position, 'executive', $personIndex++);
            }
        }

        while ($i < count($lines)) {
            $line = $lines[$i++] ?? null;
            if (!$line) {
                continue;
            }

            if (Str::startsWith($line, 'Ayrıca space')) {
                $line = $lines[$i++] ?? null;
            }

            if (!$line || !in_array($line, $departmentHeadings, true)) {
                continue;
            }

            $department = [
                'name' => $line,
                'manager' => null,
                'members' => [],
                'boards' => [],
            ];
            $currentBoardIndex = null;

            while ($i < count($lines)) {
                $current = $lines[$i] ?? null;

                if (!$current || Str::startsWith($current, 'Ayrıca space') || in_array($current, $departmentHeadings, true)) {
                    break;
                }

                $i++;

                if ($this->isSectionMarker($current)) {
                    $currentBoardIndex = null;
                    continue;
                }

                if ($this->isNoOneLine($current)) {
                    continue;
                }

                if ($this->isBoardHeading($current, $departmentHeadings)) {
                    $department['boards'][] = [
                        'name' => $current,
                        'member_keys' => [],
                    ];
                    $currentBoardIndex = array_key_last($department['boards']);
                    continue;
                }

                $next = $lines[$i] ?? null;
                if (!$next || Str::startsWith($next, 'Ayrıca space') || in_array($next, $departmentHeadings, true)) {
                    continue;
                }

                if ($this->isSectionMarker($next) || $this->isNoOneLine($next) || $this->isBoardHeading($next, $departmentHeadings)) {
                    continue;
                }

                $i++;
                $person = $this->person($current, $next, $this->roleForPosition($next), $personIndex++);
                $department['members'][] = $person;

                if ($person['role'] === 'department_manager' && !$department['manager']) {
                    $department['manager'] = $person['key'];
                }

                if ($currentBoardIndex !== null) {
                    $department['boards'][$currentBoardIndex]['member_keys'][] = $person['key'];
                }
            }

            $departments[] = $department;
        }

        return [
            'executives' => $executives,
            'departments' => $departments,
        ];
    }

    private function person(string $fullName, string $position, string $role, int $index): array
    {
        $parts = preg_split('/\s+/u', trim($fullName));
        $name = array_shift($parts) ?: $fullName;

        return [
            'key' => 'p' . $index,
            'full_name' => $fullName,
            'name' => $name,
            'surname' => trim(implode(' ', $parts)) ?: $name,
            'position' => $position,
            'role' => $role,
        ];
    }

    private function departmentHeadings(): array
    {
        return [
            'Maliyyə və mühasibatlıq departamenti',
            'Satınalmalar və təminat departamenti',
            'İnsan resurslarının idarə edilməsi departamenti',
            'Beynəlxalq əlaqələr və layihələr departamenti',
            'Strategiya və məlumatların idarə edilməsi departamenti',
            'Xidmətlər departamenti',
            'İctimaiyyətlə əlaqələr və kommunikasiya departamenti',
            'Hüquq, kargüzarlığın təşkili və inzibati idarəetmə departamenti',
            'Keyfiyyətin idarə edilməsi və xidmətlərin monitorinqi departamenti',
            'Audit şöbəsi',
        ];
    }

    private function isSectionMarker(string $line): bool
    {
        return in_array($line, ['Rəhbərlik', 'Müşavirlər', 'Koordinatorlar'], true);
    }

    private function isNoOneLine(string $line): bool
    {
        return preg_match('/heç kim yoxdur|yoxdur|space müdiri boş olacaq/iu', $line) === 1;
    }

    private function isBoardHeading(string $line, array $departmentHeadings): bool
    {
        return !in_array($line, $departmentHeadings, true)
            && preg_match('/(şöbəsi|studiyası)$/iu', $line) === 1
            && preg_match('/(müdiri|rəhbəri)$/iu', $line) !== 1;
    }

    private function looksLikePosition(string $line): bool
    {
        return preg_match('/sədri|müavini|müşaviri|müdiri|mühasib|referent|mütəxəssis|inzibatçı|sürücü|xidmətçi|koordinator|dizayner|fotoqraf|videoqraf|rəhbəri|təlimatçı/iu', $line) === 1;
    }

    private function roleForPosition(string $position): string
    {
        if (preg_match('/^Departament müdiri$/iu', $position) === 1) {
            return 'department_manager';
        }

        if (preg_match('/Departament müdirinin müavini|baş mühasib/iu', $position) === 1) {
            return 'department_deputy';
        }

        if (preg_match('/şöbəsinin müdiri|studiyasının rəhbəri/iu', $position) === 1) {
            return 'section_manager';
        }

        if (preg_match('/koordinator/iu', $position) === 1) {
            return 'coordinator';
        }

        return 'employee';
    }

    private function globalRoleFor(string $role): string
    {
        return match ($role) {
            'department_manager', 'department_deputy' => UserRole::SeniorManager->value,
            'section_manager', 'coordinator' => UserRole::MiddleManager->value,
            default => UserRole::Employee->value,
        };
    }

    private function spaceRoleFor(string $role): string
    {
        return match ($role) {
            'department_manager', 'department_deputy' => 'senior_manager',
            'section_manager', 'coordinator' => 'middle_manager',
            default => 'employee',
        };
    }

    private function boardCreator(array $boardData, array $members, array $spaceEmployees, Space $space, Employee $admin): Employee
    {
        foreach ($members as $person) {
            if (in_array($person['key'], $boardData['member_keys'], true) && $person['role'] === 'section_manager') {
                return $spaceEmployees[$person['key']];
            }
        }

        if ($space->manager_employee_id) {
            return Employee::find($space->manager_employee_id) ?: $admin;
        }

        return $admin;
    }

    private function emailFor(string $fullName, array &$emailCounts): string
    {
        $base = $this->asciiSlug($fullName, '.');
        $base = trim($base, '.') ?: 'employee';
        $emailCounts[$base] = ($emailCounts[$base] ?? 0) + 1;
        $suffix = $emailCounts[$base] > 1 ? '.' . $emailCounts[$base] : '';

        return $base . $suffix . '@tis.local';
    }

    private function departmentCode(string $name, int $index): string
    {
        $prefix = strtoupper(str_replace('-', '', $this->asciiSlug($name, '-')));
        $prefix = substr($prefix, 0, 8) ?: 'DEPT';

        return $prefix . sprintf('%02d', $index);
    }

    private function asciiSlug(string $value, string $separator): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        $value = strtr($value, [
            'ə' => 'e', 'Ə' => 'e',
            'ı' => 'i', 'I' => 'i', 'İ' => 'i',
            'ö' => 'o', 'Ö' => 'o',
            'ü' => 'u', 'Ü' => 'u',
            'ğ' => 'g', 'Ğ' => 'g',
            'ç' => 'c', 'Ç' => 'c',
            'ş' => 's', 'Ş' => 's',
        ]);
        $value = preg_replace('/[^a-z0-9]+/u', $separator, $value) ?? '';
        $value = preg_replace('/' . preg_quote($separator, '/') . '+/', $separator, $value) ?? $value;

        return trim($value, $separator);
    }

    private function spaceColor(int $index): string
    {
        $colors = ['#2D5BAA', '#3264B8', '#3A6FC8', '#244F98', '#3B82F6'];

        return $colors[$index % count($colors)];
    }
}
