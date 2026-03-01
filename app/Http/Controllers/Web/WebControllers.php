<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Space;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// ── AuthWebController ─────────────────────────────────────────────────────────

class AuthWebController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'E-poçt və ya şifrə yanlışdır.'])->withInput();
        }

        $employee = Auth::user();
        if (!$employee->is_active) {
            Auth::logout();
            return back()->withErrors(['email' => 'Hesabınız deaktivdir.']);
        }

        $employee->update(['last_login_at' => now()]);
        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}

// ── DashboardWebController ────────────────────────────────────────────────────

class DashboardWebController extends Controller
{
    public function index(): View
    {
        return view('dashboard');
    }
}

// ── SpaceWebController ────────────────────────────────────────────────────────

class SpaceWebController extends Controller
{
    public function index(): View
    {
        return view('spaces.index');
    }

    public function show(Space $space): View
    {
        $this->authorize('view', $space);
        return view('spaces.show', compact('space'));
    }
}

// ── TaskWebController ─────────────────────────────────────────────────────────

class TaskWebController extends Controller
{
    public function show(Task $task): View
    {
        $this->authorize('view', $task);
        return view('tasks.show', compact('task'));
    }
}

// ── NotificationWebController ─────────────────────────────────────────────────

class NotificationWebController extends Controller
{
    public function index(): View
    {
        return view('notifications.index');
    }
}

// ── AdminWebController ────────────────────────────────────────────────────────

class AdminWebController extends Controller
{
    public function index(): View    { return view('admin.index'); }
    public function spaces(): View  { return view('admin.spaces'); }
    public function employees(): View { return view('admin.employees'); }
    public function roles(): View   { return view('admin.roles'); }
    public function settings(): View { return view('admin.settings'); }
}
