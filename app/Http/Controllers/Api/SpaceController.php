<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\SpaceMemberResource;
use App\Http\Resources\SpaceResource;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Space;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SpaceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $employee = $request->user();

        $spaces = $employee->hasGlobalAccess()
            ? Space::with('department')->withCount(['members', 'tasks'])->where('is_active', true)->get()
            : $employee->spaces()->with('department')->withCount(['members', 'tasks'])->where('is_active', true)->get();

        return response()->json(SpaceResource::collection($spaces));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Space::class);

        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'description'   => 'nullable|string',
            'color'         => 'nullable|string|size:7',
            'icon'          => 'nullable|string|max:50',
            'department_id' => 'required|exists:departments,id',
            // Optional: allow admin to assign one manager while creating
            'manager_employee_id' => 'nullable|exists:employees,id',
        ]);

        $data['created_by'] = $request->user()->id;
        $data['slug']       = Str::slug($data['name']) . '-' . Str::random(4);

        $space = Space::create($data);

        // Yaradan şəxs avtomatik senior_manager kimi əlavə olunur
        $space->members()->attach($request->user()->id, [
            'space_role' => 'senior_manager',
            'is_manager' => false,
            'added_by'   => $request->user()->id,
        ]);

        // Optional manager assignment (must be a member first)
        if (!empty($data['manager_employee_id'])) {
            $managerId = (int) $data['manager_employee_id'];

            // Ensure the manager is a member (employee role by default)
            $space->members()->syncWithoutDetaching([
                $managerId => [
                    'space_role' => 'employee',
                    'is_manager' => true,
                    'added_by'   => $request->user()->id,
                ],
            ]);

            // Ensure only one manager per space
            DB::table('space_members')
                ->where('space_id', $space->id)
                ->where('employee_id', '!=', $managerId)
                ->update(['is_manager' => false]);
        }

        // attach-dan SONRA loadCount — düzgün say üçün
        $space->loadCount('members')->load('department');

        return response()->json(new SpaceResource($space), 201);
    }

    public function show(Request $request, Space $space): JsonResponse
    {
        $this->authorize('view', $space);
        $space->load(['creator', 'department'])->loadCount(['members', 'tasks']);
        return response()->json(new SpaceResource($space));
    }

    public function update(Request $request, Space $space): JsonResponse
    {
        $this->authorize('update', $space);

        $data = $request->validate([
            'name'          => 'sometimes|string|max:100',
            'description'   => 'nullable|string',
            'color'         => 'nullable|string|size:7',
            'icon'          => 'nullable|string|max:50',
            'is_active'     => 'sometimes|boolean',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $space->update($data);
        $space->loadCount(['members', 'tasks'])->load('department');

        return response()->json(new SpaceResource($space));
    }

    public function destroy(Request $request, Space $space): JsonResponse
    {
        $this->authorize('delete', $space);
        $space->delete();
        return response()->json(['message' => 'Space silindi.']);
    }

    public function members(Request $request, Space $space): JsonResponse
    {
        $this->authorize('view', $space);
        $members = $space->members()
            ->withPivot(['space_role', 'is_manager', 'can_create_boards', 'joined_at'])
            ->orderBy('name')
            ->orderBy('surname')
            ->get();

        return response()->json([
            'data' => SpaceMemberResource::collection($members),
        ]);
    }

    public function addMember(Request $request, Space $space): JsonResponse
    {
        $this->authorize('manageMembers', $space);

        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'space_role'  => 'required|in:senior_manager,middle_manager,employee',
            'is_manager'  => 'nullable|boolean',
            'can_create_boards' => 'nullable|boolean',
        ]);

        $isManager = (bool) ($data['is_manager'] ?? false);
        $canCreateBoards = (bool) ($data['can_create_boards'] ?? false);

        if ($isManager) {
            // Only one manager per space — clear previous manager(s)
            $currentManagers = $space->members()
                ->wherePivot('is_manager', true)
                ->pluck('employees.id')
                ->all();

            foreach ($currentManagers as $id) {
                if ((int) $id === (int) $data['employee_id']) continue;
                $space->members()->updateExistingPivot((int) $id, ['is_manager' => false]);
            }
        }

        $space->members()->syncWithoutDetaching([
            $data['employee_id'] => [
                'space_role' => $data['space_role'],
                'is_manager' => $isManager,
                'can_create_boards' => $canCreateBoards,
                'added_by'   => $request->user()->id,
            ],
        ]);

        return response()->json(['message' => 'Üzv əlavə edildi.']);
    }

    public function removeMember(Request $request, Space $space, Employee $employee): JsonResponse
    {
        $this->authorize('manageMembers', $space);
        $space->members()->detach($employee->id);
        return response()->json(['message' => 'Üzv silindi.']);
    }

    // ── Departamentlər siyahısı (modal üçün) ─────────────────────────────
    public function departments(): JsonResponse
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        return response()->json($departments);
    }
}
