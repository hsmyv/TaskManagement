<?php

use App\Http\Controllers\Api\AdminEmployeeController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChecklistController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SpaceController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

// ── Public ────────────────────────────────────────────────────────────────────
Route::post('/auth/login',  [AuthController::class, 'login']);

// ── Authenticated ─────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'active.employee'])->group(function () {

    // Auth
    Route::post('/auth/logout',  [AuthController::class, 'logout']);
    Route::get('/auth/me',       [AuthController::class, 'me']);

    // Dashboard
    Route::get('/dashboard',     [DashboardController::class, 'index']);

    // Spaces
    Route::apiResource('spaces', SpaceController::class);
    Route::post('/spaces/{space}/members',          [SpaceController::class, 'addMember']);
    Route::delete('/spaces/{space}/members/{employee}', [SpaceController::class, 'removeMember']);
    Route::get('/spaces/{space}/members',           [SpaceController::class, 'members']);

    // Tasks
    Route::get('/spaces/{space}/tasks',             [TaskController::class, 'index']);
    Route::post('/spaces/{space}/tasks',            [TaskController::class, 'store']);
    Route::get('/tasks/{task}',                     [TaskController::class, 'show']);
    Route::put('/tasks/{task}',                     [TaskController::class, 'update']);
    Route::delete('/tasks/{task}',                  [TaskController::class, 'destroy']);
    Route::patch('/tasks/{task}/status',            [TaskController::class, 'updateStatus']);
    Route::patch('/tasks/{task}/approve',           [TaskController::class, 'approve']);
    Route::patch('/tasks/{task}/assignees',         [TaskController::class, 'updateAssignees']);
    Route::patch('/tasks/{task}/order',             [TaskController::class, 'updateOrder']);  // Drag & Drop

    // Subtasks
    Route::get('/tasks/{task}/subtasks',            [TaskController::class, 'subtasks']);
    Route::post('/tasks/{task}/subtasks',           [TaskController::class, 'storeSubtask']);

    // Checklist
    Route::post('/tasks/{task}/checklists',         [ChecklistController::class, 'store']);
    Route::patch('/checklists/{checklist}/toggle',  [ChecklistController::class, 'toggle']);
    Route::put('/checklists/{checklist}',           [ChecklistController::class, 'update']);
    Route::delete('/checklists/{checklist}',        [ChecklistController::class, 'destroy']);
    Route::post('/tasks/{task}/checklists/reorder', [ChecklistController::class, 'reorder']);

    // Comments
    Route::get('/tasks/{task}/comments',            [CommentController::class, 'index']);
    Route::post('/tasks/{task}/comments',           [CommentController::class, 'store']);
    Route::delete('/comments/{comment}',            [CommentController::class, 'destroy']);

    // Attachments
    Route::get('/tasks/{task}/attachments',         [AttachmentController::class, 'index']);
    Route::post('/tasks/{task}/attachments',        [AttachmentController::class, 'store']);
    Route::delete('/attachments/{attachment}',      [AttachmentController::class, 'destroy']);
    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download']);

    // Notifications
    Route::get('/notifications',                    [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::patch('/notifications/read-all',         [NotificationController::class, 'markAllRead']);
    Route::get('/notifications/unread-count',       [NotificationController::class, 'unreadCount']);

    // Employees (search/select üçün)
    Route::get('/employees',                        [AuthController::class, 'employees']);
    Route::get('/employees/search',                 [AuthController::class, 'searchEmployees']);
    Route::get('/departments',                      [SpaceController::class, 'departments']);


});
Route::middleware(['auth:sanctum', 'active.employee', 'role:administrator'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/employees',                    [AdminEmployeeController::class, 'index']);
        Route::post('/employees',                   [AdminEmployeeController::class, 'store']);
        Route::put('/employees/{employee}',         [AdminEmployeeController::class, 'update']);
        Route::delete('/employees/{employee}',      [AdminEmployeeController::class, 'destroy']);
        Route::patch('/employees/{employee}/toggle',[AdminEmployeeController::class, 'toggleActive']);
        Route::get('/roles',                        [AdminEmployeeController::class, 'roles']);
    });
