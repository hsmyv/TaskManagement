<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Space;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// ── DashboardWebController ────────────────────────────────────────────────────

class DashboardWebController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $employee = Auth::user();

        if ($employee && !$employee->hasGlobalAccess()) {
            $targetSpace = $employee->spaces()
                ->where('spaces.is_active', true)
                ->orderByDesc('space_members.is_manager')
                ->orderBy('spaces.id')
                ->first();

            if ($targetSpace) {
                return redirect()->route('spaces.show', $targetSpace);
            }
        }

        return view('dashboard');
    }
}
