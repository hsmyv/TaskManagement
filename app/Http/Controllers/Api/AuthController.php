<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
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
                    ->orWhere('email', 'ai@tis.local');
            });
        }

        return response()->json(EmployeeResource::collection($query->limit(20)->get()));
    }

    private function ensureAiEmployee(): Employee
    {
        return Employee::updateOrCreate(
            ['email' => 'ai@tis.local'],
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
