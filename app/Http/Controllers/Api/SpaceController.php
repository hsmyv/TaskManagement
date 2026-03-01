<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\SpaceResource;
use App\Models\Employee;
use App\Models\Space;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SpaceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $employee = $request->user();

        $spaces = $employee->hasGlobalAccess()
            ? Space::withCount(['members', 'tasks'])->where('is_active', true)->get()
            : $employee->spaces()->withCount(['members', 'tasks'])->where('is_active', true)->get();

        return response()->json(SpaceResource::collection($spaces));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Space::class);

        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string',
            'color'       => 'nullable|string|size:7',
            'icon'        => 'nullable|string|max:50',
        ]);

        $data['created_by'] = $request->user()->id;
        $data['slug']       = Str::slug($data['name']) . '-' . Str::random(4);

        $space = Space::create($data);

        // Yaradan şəxs avtomatik senior_manager kimi əlavə olunur
        $space->members()->attach($request->user()->id, [
            'space_role' => 'senior_manager',
            'added_by'   => $request->user()->id,
        ]);

        return response()->json(new SpaceResource($space), 201);
    }

    public function show(Request $request, Space $space): JsonResponse
    {
        $this->authorize('view', $space);
        $space->load(['creator'])->loadCount(['members', 'tasks']);
        return response()->json(new SpaceResource($space));
    }

    public function update(Request $request, Space $space): JsonResponse
    {
        $this->authorize('update', $space);

        $data = $request->validate([
            'name'        => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'color'       => 'nullable|string|size:7',
            'icon'        => 'nullable|string|max:50',
            'is_active'   => 'sometimes|boolean',
        ]);

        $space->update($data);
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
        $members = $space->members()->withPivot(['space_role', 'joined_at'])->get();
        return response()->json(EmployeeResource::collection($members));
    }

    public function addMember(Request $request, Space $space): JsonResponse
    {
        $this->authorize('manageMembers', $space);

        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'space_role'  => 'required|in:senior_manager,middle_manager,employee',
        ]);

        $space->members()->syncWithoutDetaching([
            $data['employee_id'] => [
                'space_role' => $data['space_role'],
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
}
