<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\Checklist;
use App\Models\Comment;
use App\Models\Employee;
use App\Models\Space;
use App\Models\StatusHistory;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FakeTaskSeeder extends Seeder
{
    private array $statuses = [
        Task::STATUS_TODO,
        Task::STATUS_IN_PROGRESS,
        Task::STATUS_WAITING_FOR_APPROVE,
        Task::STATUS_COMPLETED,
        Task::STATUS_CANCELED,
    ];

    private array $priorities = [
        Task::PRIORITY_LOW,
        Task::PRIORITY_MEDIUM,
        Task::PRIORITY_HIGH,
        Task::PRIORITY_URGENT,
    ];

    public function run(): void
    {
        Task::withTrashed()
            ->where('description', 'like', 'Seed demo:%')
            ->forceDelete();

        $farid = Employee::where('email', 'ferid.memmedov@sosial.gov.az')->first()
            ?: Employee::role('executive_manager')->orderBy('id')->first()
            ?: Employee::orderBy('id')->first();

        if (!$farid) {
            $this->command?->warn('Fake task seeding skipped: employee not found.');
            return;
        }

        $spaces = Space::query()
            ->with(['members', 'boards' => fn ($query) => $query->whereNull('archived_at')->orderBy('id')])
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $created = 0;

        foreach ($spaces as $spaceIndex => $space) {
            $manager = $space->manager_employee_id
                ? Employee::find($space->manager_employee_id)
                : null;

            $creatorPool = collect([$farid, $manager])->filter()->unique('id')->values();
            $members = $space->members->where('id', '!=', $farid->id)->values();

            if ($members->isEmpty()) {
                continue;
            }

            $boards = $space->boards->values();
            if ($boards->isEmpty()) {
                $boards = collect([null]);
            }

            foreach ($this->statuses as $statusIndex => $status) {
                $board = $boards[$statusIndex % $boards->count()];
                if ($board instanceof Board && !$board->deadline) {
                    $board->update(['deadline' => now()->addDays(14 + $statusIndex + $spaceIndex)->toDateString()]);
                }

                $creator = $creatorPool[$statusIndex % $creatorPool->count()];
                $assignees = $members->slice($statusIndex % max(1, $members->count()), 2)->values();
                if ($assignees->isEmpty()) {
                    $assignees = $members->take(1)->values();
                }

                $task = Task::create([
                    'title' => $this->taskTitle($spaceIndex, $statusIndex, $status),
                    'description' => 'Seed demo: ' . $space->name . ' üzrə test tapşırığı. Bu məlumatlar dashboard, board, status, subtask və filter sınaqları üçündür.',
                    'space_id' => $space->id,
                    'board_id' => $board?->id,
                    'board_position' => ($statusIndex + 1) * 100,
                    'status' => $status,
                    'priority' => $this->priorities[($spaceIndex + $statusIndex) % count($this->priorities)],
                    'start_date' => now()->subDays(5 - min($statusIndex, 4))->toDateString(),
                    'due_date' => $this->dueDateFor($status, $spaceIndex, $statusIndex),
                    'estimated_hours' => 4 + ($statusIndex * 2),
                    'visibility' => Task::VISIBILITY_ALL,
                    'require_approval' => true,
                    'created_by' => $creator->id,
                    'assigned_by' => $creator->id,
                ]);

                $this->attachAssignees($task, $assignees, $creator);
                $this->createChecklist($task, $assignees->first(), $status);
                $this->createSubtasks($task, $space, $board, $creator, $assignees, $status);
                $this->createComments($task, $creator, $assignees->first());
                $this->createStatusHistory($task, $creator);

                $created++;
            }
        }

        $this->command?->info("Fake task seeding tamamlandı: {$created} əsas tapşırıq yaradıldı.");
    }

    private function attachAssignees(Task $task, $assignees, Employee $assigner): void
    {
        foreach ($assignees as $assignee) {
            $task->assignees()->syncWithoutDetaching([
                $assignee->id => [
                    'assigned_by' => $assigner->id,
                    'assigned_at' => now()->subDays(1),
                ],
            ]);
        }
    }

    private function createChecklist(Task $task, ?Employee $employee, string $status): void
    {
        $items = ['Tələblər dəqiqləşdirilsin', 'İcra planı hazırlansın', 'Nəticə yoxlanılsın'];
        $doneCount = match ($status) {
            Task::STATUS_COMPLETED => 3,
            Task::STATUS_WAITING_FOR_APPROVE => 2,
            Task::STATUS_IN_PROGRESS => 1,
            default => 0,
        };

        foreach ($items as $index => $title) {
            $isDone = $index < $doneCount;
            Checklist::create([
                'task_id' => $task->id,
                'title' => $title,
                'is_done' => $isDone,
                'order' => $index + 1,
                'completed_by' => $isDone ? $employee?->id : null,
                'completed_at' => $isDone ? now()->subHours(8 - $index) : null,
            ]);
        }
    }

    private function createSubtasks(Task $task, Space $space, ?Board $board, Employee $creator, $assignees, string $parentStatus): void
    {
        $subtaskStatuses = match ($parentStatus) {
            Task::STATUS_COMPLETED => [Task::STATUS_COMPLETED, Task::STATUS_COMPLETED],
            Task::STATUS_WAITING_FOR_APPROVE => [Task::STATUS_COMPLETED, Task::STATUS_WAITING_FOR_APPROVE],
            Task::STATUS_CANCELED => [Task::STATUS_CANCELED, Task::STATUS_TODO],
            Task::STATUS_IN_PROGRESS => [Task::STATUS_COMPLETED, Task::STATUS_IN_PROGRESS],
            default => [Task::STATUS_TODO, Task::STATUS_TODO],
        };

        foreach ($subtaskStatuses as $index => $status) {
            $assignee = $assignees[$index % max(1, $assignees->count())] ?? $assignees->first();

            $subtask = Task::create([
                'title' => ($index + 1) . '. alt tapşırıq - ' . $task->title,
                'description' => 'Seed demo: Alt tapşırıq nümunəsi.',
                'space_id' => $space->id,
                'board_id' => $board?->id,
                'board_position' => $task->board_position + $index + 1,
                'parent_task_id' => $task->id,
                'status' => $status,
                'priority' => $task->priority,
                'start_date' => $task->start_date,
                'due_date' => $task->due_date,
                'visibility' => Task::VISIBILITY_ALL,
                'require_approval' => true,
                'created_by' => $creator->id,
                'assigned_by' => $creator->id,
            ]);

            if ($assignee) {
                $this->attachAssignees($subtask, collect([$assignee]), $creator);
            }

            $this->createStatusHistory($subtask, $creator);
        }
    }

    private function createComments(Task $task, Employee $creator, ?Employee $assignee): void
    {
        $comment = Comment::create([
            'task_id' => $task->id,
            'employee_id' => $creator->id,
            'body' => 'Tapşırıq yaradıldı və ilkin məlumatlar əlavə olundu.',
        ]);

        if ($assignee) {
            Comment::create([
                'task_id' => $task->id,
                'employee_id' => $assignee->id,
                'parent_id' => $comment->id,
                'body' => 'Qəbul edildi, icra detalları yoxlanılır.',
            ]);
        }
    }

    private function createStatusHistory(Task $task, Employee $changer): void
    {
        StatusHistory::create([
            'task_id' => $task->id,
            'from_status' => null,
            'to_status' => $task->status,
            'changed_by' => $changer->id,
            'comment' => 'Seed demo statusu',
            'changed_at' => now()->subHours(3),
        ]);
    }

    private function taskTitle(int $spaceIndex, int $statusIndex, string $status): string
    {
        $labels = [
            Task::STATUS_TODO => 'Yeni icra planı',
            Task::STATUS_IN_PROGRESS => 'Cari icra monitorinqi',
            Task::STATUS_WAITING_FOR_APPROVE => 'Təsdiqə göndərilən hesabat',
            Task::STATUS_COMPLETED => 'Tamamlanmış nəticə yoxlaması',
            Task::STATUS_CANCELED => 'Ləğv edilmiş köhnə tələb',
        ];

        return $labels[$status] . ' #' . ($spaceIndex + 1) . '-' . ($statusIndex + 1);
    }

    private function dueDateFor(string $status, int $spaceIndex, int $statusIndex): string
    {
        $date = match ($status) {
            Task::STATUS_TODO => now()->addDays(7 + $spaceIndex),
            Task::STATUS_IN_PROGRESS => now()->addDays(3 + $statusIndex),
            Task::STATUS_WAITING_FOR_APPROVE => now()->addDays(1),
            Task::STATUS_COMPLETED => now()->subDays(2),
            Task::STATUS_CANCELED => now()->subDays(6),
            default => now()->addWeek(),
        };

        return Carbon::parse($date)->toDateString();
    }
}
