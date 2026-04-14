<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Space;
use Illuminate\View\View;

class BoardWebController extends Controller
{
    public function show(Space $space, Board $board): View
    {
        abort_unless($board->space_id === $space->id, 404);
        $this->authorize('view', $board);

        return view('boards.show', compact('space', 'board'));
    }
}

