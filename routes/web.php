<?php

use App\Http\Controllers\Web\DashboardWebController;
use App\Http\Controllers\Web\SpaceWebController;
use App\Http\Controllers\Web\TaskWebController;
use App\Http\Controllers\Web\AdminWebController;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\NotificationWebController;
use App\Http\Controllers\Web\BoardWebController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login',  [AuthWebController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthWebController::class, 'login'])->name('login.post');
});

Route::middleware(['auth', 'active.employee'])->group(function () {
    Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardWebController::class, 'index'])->name('dashboard');
    Route::get('/statistics', [DashboardWebController::class, 'statistics'])->name('dashboard.statistics');

    // Route::get('/spaces',              [SpaceWebController::class, 'index'])->name('spaces.index');
    Route::get('/spaces/{space}',      [SpaceWebController::class, 'show'])->name('spaces.detail');
    Route::get('/spaces/{space}/boards/{board}', [BoardWebController::class, 'show'])->name('boards.show');

    Route::get('/tasks/{task}',        [TaskWebController::class, 'show'])->name('tasks.show');

    Route::get('/notifications',       [NotificationWebController::class, 'index'])->name('notifications.index');

    Route::middleware('role:administrator')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/',                         [AdminWebController::class, 'index'])->name('index');
        Route::get('/spaces',                   [AdminWebController::class, 'spaces'])->name('spaces');
        Route::get('/employees',                [AdminWebController::class, 'employees'])->name('employees');
        Route::get('/roles',                    [AdminWebController::class, 'roles'])->name('roles');
        Route::get('/settings',                 [AdminWebController::class, 'settings'])->name('settings');
    });
});
