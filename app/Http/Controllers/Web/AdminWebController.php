<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Space;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
// ── AdminWebController ────────────────────────────────────────────────────────

class AdminWebController extends Controller
{
    public function index(): View    { return view('admin.index'); }
    public function spaces(): View  { return view('admin.spaces'); }
    public function employees(): View { return view('admin.employees'); }
    public function roles(): View   { return view('admin.roles'); }
    public function settings(): View { return view('admin.settings'); }
}
