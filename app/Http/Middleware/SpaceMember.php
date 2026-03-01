<?php

namespace App\Http\Middleware;

use App\Models\Space;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SpaceMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $employee = $request->user();
        $space    = $request->route('space');

        if (!$space instanceof Space) {
            $space = Space::find($space);
        }

        if (!$space) {
            return response()->json(['message' => 'Space tapılmadı.'], 404);
        }

        if (!$employee->hasGlobalAccess() && !$employee->isMemberOf($space)) {
            return response()->json([
                'message' => 'Bu Space-ə giriş icazəniz yoxdur.',
            ], 403);
        }

        return $next($request);
    }
}
