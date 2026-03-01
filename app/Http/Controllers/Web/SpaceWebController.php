<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Space;
use App\Models\Task;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

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
