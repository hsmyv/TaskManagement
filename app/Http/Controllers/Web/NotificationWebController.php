<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Space;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// ── NotificationWebController ─────────────────────────────────────────────────

class NotificationWebController extends Controller
{
    public function index(): View
    {
        return view('notifications.index');
    }
}
