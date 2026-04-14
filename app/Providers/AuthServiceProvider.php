<?php

namespace App\Providers;

use App\Models\Space;
use App\Models\Task;
use App\Models\Board;
use App\Models\BoardList;
use App\Policies\BoardListPolicy;
use App\Policies\BoardPolicy;
use App\Policies\SpacePolicy;
use App\Policies\TaskPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Task::class  => TaskPolicy::class,
        Space::class => SpacePolicy::class,
        Board::class => BoardPolicy::class,
        BoardList::class => BoardListPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
