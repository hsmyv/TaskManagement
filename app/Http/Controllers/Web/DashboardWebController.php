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
    public function index(): View
    {
        $employee = Auth::user();

        // Departament müdiri (space manager) daxil olduqda birbaşa idarə etdiyi space açılsın
        if ($employee && !$employee->hasGlobalAccess()) {
            $managedSpace = $employee->spaces()
                ->wherePivot('is_manager', true)
                ->orderBy('spaces.id')
                ->first();

            if ($managedSpace) {
                redirect()->route('spaces.show', $managedSpace)->send();
            }
        }

        return view('dashboard');
    }
}
