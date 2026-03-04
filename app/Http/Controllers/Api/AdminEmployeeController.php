<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class AdminEmployeeController extends Controller
{
    public function __construct()
    {
    }

    // ── Siyahı + axtarış ─────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $q          = $request->query('q', '');
        $deptId     = $request->query('department_id');
        $roleFilter = $request->query('role');
        $status     = $request->query('status'); // 'active' | 'inactive' | ''

        $employees = Employee::with('department')
            ->when($q, fn($query) =>
                $query->where(function ($q2) use ($q) {
                    $q2->where('name',    'like', "%{$q}%")
                       ->orWhere('surname', 'like', "%{$q}%")
                       ->orWhere('email',   'like', "%{$q}%")
                       ->orWhere('position','like', "%{$q}%");
                })
            )
            ->when($deptId,     fn($query) => $query->where('department_id', $deptId))
            ->when($status === 'active',   fn($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn($query) => $query->where('is_active', false))
            ->when($roleFilter, fn($query) => $query->role($roleFilter))
            ->orderBy('surname')
            ->orderBy('name')
            ->paginate(30);

        return response()->json([
            'data' => EmployeeResource::collection($employees->items()),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'last_page'    => $employees->lastPage(),
                'total'        => $employees->total(),
            ],
        ]);
    }

    // ── Yarat ─────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'surname'       => 'required|string|max:100',
            'patronymic'    => 'nullable|string|max:100',
            'email'         => 'required|email|unique:employees,email',
            'password'      => 'required|string|min:6',
            'phone'         => 'nullable|string|max:30',
            'position'      => 'nullable|string|max:150',
            'department_id' => 'nullable|exists:departments,id',
            'role'          => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
            'is_active'     => 'boolean',
        ]);

        $role = $data['role'];
        unset($data['role']);
        $data['password'] = Hash::make($data['password']);

        $employee = Employee::create($data);
        $employee->assignRole($role);
        $employee->load('department');

        return response()->json(new EmployeeResource($employee), 201);
    }

    // ── Yenilə ────────────────────────────────────────────────────────────
    public function update(Request $request, Employee $employee): JsonResponse
    {
        $data = $request->validate([
            'name'          => 'sometimes|string|max:100',
            'surname'       => 'sometimes|string|max:100',
            'patronymic'    => 'nullable|string|max:100',
            'email'         => ['sometimes', 'email', Rule::unique('employees', 'email')->ignore($employee->id)],
            'password'      => 'nullable|string|min:6',
            'phone'         => 'nullable|string|max:30',
            'position'      => 'nullable|string|max:150',
            'department_id' => 'nullable|exists:departments,id',
            'role'          => ['nullable', Rule::in(array_column(UserRole::cases(), 'value'))],
            'is_active'     => 'sometimes|boolean',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if (isset($data['role'])) {
            $employee->syncRoles([$data['role']]);
            unset($data['role']);
        }

        $employee->update($data);
        $employee->load('department');

        return response()->json(new EmployeeResource($employee));
    }

    // ── Sil ───────────────────────────────────────────────────────────────
    public function destroy(Employee $employee): JsonResponse
    {
        // Özünü silə bilməz
        if ($employee->id === request()->user()->id) {
            return response()->json(['message' => 'Öz hesabınızı silə bilməzsiniz.'], 422);
        }

        $employee->delete();
        return response()->json(['message' => 'Əməkdaş silindi.']);
    }

    // ── Aktiv/passiv ──────────────────────────────────────────────────────
    public function toggleActive(Employee $employee): JsonResponse
    {
        if ($employee->id === request()->user()->id) {
            return response()->json(['message' => 'Öz hesabınızı deaktiv edə bilməzsiniz.'], 422);
        }

        $employee->update(['is_active' => !$employee->is_active]);
        $employee->load('department');

        return response()->json(new EmployeeResource($employee));
    }

    // ── Rollar siyahısı ───────────────────────────────────────────────────
    public function roles(): JsonResponse
    {
        $roles = Role::withCount('permissions')
            ->with('permissions')
            ->get()
            ->map(fn($r) => [
                'id'               => $r->id,
                'name'             => $r->name,
                'label'            => UserRole::tryFrom($r->name)?->label() ?? $r->name,
                'permissions_count'=> $r->permissions_count,
                'permissions'      => $r->permissions->pluck('name'),
            ]);

        return response()->json($roles);
    }
}
