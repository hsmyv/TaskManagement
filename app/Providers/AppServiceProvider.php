<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    Gate::policy(\App\Models\Task::class, \App\Policies\TaskPolicy::class);
    Gate::policy(\App\Models\Space::class, \App\Policies\SpacePolicy::class);
}
}
