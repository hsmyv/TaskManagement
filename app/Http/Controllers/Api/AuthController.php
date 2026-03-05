<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function employees(Request $request): JsonResponse
    {
        $employees = Employee::active()
            ->orderBy('name')
            ->get();
        return response()->json(EmployeeResource::collection($employees));
    }

    public function searchEmployees(Request $request): JsonResponse
    {
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
            $query->whereHas('spaces', fn($q) => $q->where('spaces.id', $spaceId));
        }

        return response()->json(EmployeeResource::collection($query->limit(20)->get()));
    }
}
