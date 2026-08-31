<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Space;
use Illuminate\View\View;


class SpaceWebController extends Controller
{

    public function index(): View
    {
        return view('spaces.index');
    }

public function show(Space $space): View
{
    $this->authorize('view', $space);

    $space->load([
        'department',
        'manager',
        'members',
        'boards' => fn ($query) => $query->withCount('tasks')->latest(),
        'tasks' => fn ($query) => $query->with(['assignees', 'board'])->latest(),
    ])->loadCount(['members', 'boards', 'tasks']);

    return view('spaces.show', compact('space'));
}
}
