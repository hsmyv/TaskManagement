<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['E-poçt və ya şifrə yanlışdır.'],
            ]);
        }

        $employee = Employee::where('email', $request->email)->firstOrFail();

        if (!$employee->is_active) {
            Auth::logout();
            return response()->json(['message' => 'Hesabınız deaktivdir.'], 403);
        }

        $employee->update(['last_login_at' => now()]);
        $token = $employee->createToken('tis-api')->plainTextToken;

        return response()->json([
            'token'    => $token,
            'employee' => new EmployeeResource($employee),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Çıxış edildi.']);
    }

    public function me(Request $request): JsonResponse
    {
        $employee = $request->user()->load(['spaces']);
        return response()->json(new EmployeeResource($employee));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $employee = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $employee->name = $data['name'];
        $employee->surname = $data['surname'];

        if ($request->hasFile('avatar')) {
            if ($employee->avatar) {
                Storage::disk('public')->delete($employee->avatar);
            }
            $employee->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        $employee->save();

        return response()->json(new EmployeeResource($employee->fresh()));
    }

    public function employees(Request $request): JsonResponse
    {
        $this->ensureAiEmployee();

        $employees = Employee::active()
            ->orderBy('name')
            ->get();
        return response()->json(EmployeeResource::collection($employees));
    }

    public function searchEmployees(Request $request): JsonResponse
    {
        $this->ensureAiEmployee();

        $q       = $request->query('q', '');
        $spaceId = $request->query('space_id');

        $query = Employee::where('is_active', true)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('surname', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });

        // Space üzvləri ilə məhdudlaşdır
        if ($spaceId) {
            $query->where(function ($query) use ($spaceId) {
                $query->whereHas('spaces', fn($q) => $q->where('spaces.id', $spaceId))
                    ->orWhere('email', 'ai@sosial.gov.az');
            });
        }

        return response()->json(EmployeeResource::collection($query->limit(20)->get()));
    }

    public function employeeProfile(Request $request, Employee $employee): JsonResponse
    {
        $employee->load(['department', 'spaces' => fn ($query) => $query
            ->withPivot(['space_role', 'is_manager', 'can_create_boards', 'joined_at'])
            ->withCount([
                'tasks as tasks_count' => fn ($taskQuery) => $taskQuery->whereNull('parent_task_id'),
            ])
            ->orderBy('name')
        ]);

        $taskScope = Task::query()
            ->whereNull('parent_task_id')
            ->where(function ($query) use ($employee) {
                $query->where('created_by', $employee->id)
                    ->orWhere('assigned_by', $employee->id)
                    ->orWhereHas('assignees', fn ($assignees) => $assignees->where('employees.id', $employee->id))
                    ->orWhereHas('subtasks', function ($subtasks) use ($employee) {
                        $subtasks->where('created_by', $employee->id)
                            ->orWhere('assigned_by', $employee->id)
                            ->orWhereHas('assignees', fn ($assignees) => $assignees->where('employees.id', $employee->id));
                    });
            });

        $statusCounts = (clone $taskScope)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return response()->json([
            'employee' => new EmployeeResource($employee),
            'spaces' => $employee->spaces->map(function ($space) use ($employee) {
                $spaceTaskScope = Task::query()
                    ->where('space_id', $space->id)
                    ->whereNull('parent_task_id')
                    ->where(function ($query) use ($employee) {
                        $query->where('created_by', $employee->id)
                            ->orWhere('assigned_by', $employee->id)
                            ->orWhereHas('assignees', fn ($assignees) => $assignees->where('employees.id', $employee->id))
                            ->orWhereHas('subtasks', function ($subtasks) use ($employee) {
                                $subtasks->where('created_by', $employee->id)
                                    ->orWhere('assigned_by', $employee->id)
                                    ->orWhereHas('assignees', fn ($assignees) => $assignees->where('employees.id', $employee->id));
                            });
                    });

                $spaceStatusCounts = (clone $spaceTaskScope)
                    ->selectRaw('status, count(*) as aggregate')
                    ->groupBy('status')
                    ->pluck('aggregate', 'status');

                return [
                    'id' => $space->id,
                    'name' => $space->name,
                    'space_role' => $space->pivot?->space_role,
                    'is_manager' => (bool) ($space->pivot?->is_manager ?? false),
                    'can_create_boards' => (bool) ($space->pivot?->can_create_boards ?? false),
                    'joined_at' => $space->pivot?->joined_at,
                    'tasks_count' => (clone $spaceTaskScope)->count(),
                    'in_progress_count' => (int) ($spaceStatusCounts['in_progress'] ?? 0),
                    'waiting_count' => (int) ($spaceStatusCounts['waiting_for_approve'] ?? 0),
                ];
            })->values(),
            'task_stats' => [
                'total' => (clone $taskScope)->count(),
                'todo' => (int) ($statusCounts['todo'] ?? 0),
                'in_progress' => (int) ($statusCounts['in_progress'] ?? 0),
                'waiting_for_approve' => (int) ($statusCounts['waiting_for_approve'] ?? 0),
                'completed' => (int) ($statusCounts['completed'] ?? 0),
                'canceled' => (int) ($statusCounts['canceled'] ?? 0),
            ],
        ]);
    }

    private function ensureAiEmployee(): Employee
    {
        return Employee::updateOrCreate(
            ['email' => 'ai@sosial.gov.az'],
            [
                'name' => 'AI',
                'surname' => '',
                'password' => Hash::make('password123!'),
                'position' => 'AI köməkçi',
                'source_type' => 'local',
                'is_active' => true,
            ]
        );
    }
}
