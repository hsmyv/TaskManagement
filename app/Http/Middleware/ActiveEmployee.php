<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActiveEmployee
{
    public function handle(Request $request, Closure $next): Response
    {
        $employee = $request->user();

        if ($employee && !$employee->is_active) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Hesabınız deaktivdir. Administratorla əlaqə saxlayın.',
                ], 403);
            }

            auth()->logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Hesabınız deaktivdir.']);
        }

        return $next($request);
    }
}
