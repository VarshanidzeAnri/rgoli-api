<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Common\PublicController;
use App\Http\Controllers\Common\WorkspaceController;
use App\Http\Controllers\File\FileController;
use App\Http\Controllers\Finance\ClientPaymentController;
use App\Http\Controllers\Finance\SalaryRequestController;
use App\Http\Controllers\Issue\CommentController;
use App\Http\Controllers\Issue\IssueController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Project\PhaseController;
use App\Http\Controllers\Project\ProjectController;
use App\Http\Controllers\Report\ReportController;
use App\Http\Controllers\Role\RoleController;
use App\Http\Controllers\Sprint\SprintController;
use App\Http\Controllers\System\SystemController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1  (prefix /api/v1, see bootstrap/app.php)
|--------------------------------------------------------------------------
| Every route has two layers: `permission:` middleware (coarse gate) and a
| Policy check in the controller (permission + project visibility).
*/

// ── Public ──────────────────────────────────────────────────────────────
Route::get('config', [PublicController::class, 'config'])->name('config');
Route::get('public/stats', [PublicController::class, 'stats'])->name('public.stats');
Route::get('auth/demo-accounts', [PublicController::class, 'demoAccounts'])->name('auth.demo-accounts');

Route::middleware('guest:sanctum')->prefix('auth')->name('auth.')->group(function (): void {
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login')->name('login');
    Route::post('forgot-password', [PasswordResetController::class, 'store'])->middleware('throttle:password-reset')->name('password.email');
    Route::post('reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:password-reset')->name('password.update');
});

Route::get('exports/{file}', [SystemController::class, 'downloadExport'])->middleware('signed')->name('exports.download');

// ── Authenticated ───────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('auth/logout', [AuthenticatedSessionController::class, 'destroy'])->name('auth.logout');

    // Profile
    Route::prefix('me')->name('me.')->controller(ProfileController::class)->group(function (): void {
        Route::get('/', 'show')->name('show');
        Route::patch('/', 'update')->name('update');
        Route::post('avatar', 'avatar')->middleware('throttle:uploads')->name('avatar');
        Route::put('password', 'password')->name('password');
    });

    // Workspace (cross-feature reads)
    Route::controller(WorkspaceController::class)->group(function (): void {
        Route::get('dashboard', 'dashboard')->middleware('permission:projects.view')->name('dashboard');
        Route::get('calendar', 'calendar')->middleware('permission:projects.view')->name('calendar');
        Route::get('search', 'search')->middleware('permission:projects.view')->name('search');
        Route::get('activity', 'activity')->middleware('permission:projects.view')->name('activity');
        Route::get('people', 'people')->name('people');
        Route::get('notifications', 'notifications')->name('notifications.index');
        Route::post('notifications/read-all', 'readAllNotifications')->name('notifications.read-all');
        Route::post('notifications/{id}/read', 'readNotification')->name('notifications.read');
    });

    // Projects
    Route::controller(ProjectController::class)->group(function (): void {
        Route::get('projects', 'index')->middleware('permission:projects.view')->name('projects.index');
        Route::post('projects', 'store')->middleware('permission:projects.create')->name('projects.store');
        Route::get('projects/{project}', 'show')->middleware('permission:projects.view')->name('projects.show');
        Route::patch('projects/{project}', 'update')->middleware('permission:projects.update')->name('projects.update');
        Route::delete('projects/{project}', 'destroy')->middleware('permission:projects.delete')->name('projects.destroy');
        Route::patch('projects/{project}/location', 'location')->middleware('permission:locations.update')->name('projects.location');
        Route::put('projects/{project}/members', 'members')->middleware('permission:projects.manage_members')->name('projects.members');
        Route::get('projects/{project}/activity', 'activity')->middleware('permission:projects.view')->name('projects.activity');
        Route::get('map/projects', 'map')->middleware('permission:map.view')->name('map.projects');
        Route::get('timeline', 'timeline')->middleware('permission:projects.view')->name('timeline');
    });

    // Phases
    Route::controller(PhaseController::class)->group(function (): void {
        Route::get('projects/{project}/phases', 'index')->middleware('permission:projects.view')->name('phases.index');
        Route::post('projects/{project}/phases', 'store')->middleware('permission:phases.manage')->name('phases.store');
        Route::put('projects/{project}/phases/order', 'reorder')->middleware('permission:phases.manage')->name('phases.reorder');
        Route::patch('phases/{phase}', 'update')->middleware('permission:phases.manage')->name('phases.update');
        Route::delete('phases/{phase}', 'destroy')->middleware('permission:phases.manage')->name('phases.destroy');
    });

    // Issues
    Route::controller(IssueController::class)->group(function (): void {
        Route::get('issues', 'index')->middleware('permission:issues.view')->name('issues.index');
        Route::get('issues/board', 'board')->middleware('permission:issues.view')->name('issues.board');
        Route::post('issues', 'store')->middleware('permission:issues.create')->name('issues.store');
        Route::get('issues/{issue}', 'show')->middleware('permission:issues.view')->name('issues.show');
        Route::patch('issues/{issue}', 'update')->middleware('permission:issues.update')->name('issues.update');
        Route::patch('issues/{issue}/move', 'move')->middleware('permission:issues.move')->name('issues.move');
        Route::delete('issues/{issue}', 'destroy')->middleware('permission:issues.delete')->name('issues.destroy');
    });

    Route::controller(CommentController::class)->group(function (): void {
        Route::get('issues/{issue}/comments', 'index')->middleware('permission:issues.view')->name('comments.index');
        Route::post('issues/{issue}/comments', 'store')->middleware('permission:issues.comment')->name('comments.store');
        Route::patch('comments/{comment}', 'update')->middleware('permission:issues.comment')->name('comments.update');
        Route::delete('comments/{comment}', 'destroy')->middleware('permission:issues.comment')->name('comments.destroy');
    });

    // Sprints
    Route::controller(SprintController::class)->group(function (): void {
        Route::get('sprints', 'index')->middleware('permission:issues.view')->name('sprints.index');
        Route::middleware('permission:sprints.manage')->group(function (): void {
            Route::post('sprints', 'store')->name('sprints.store');
            Route::patch('sprints/{sprint}', 'update')->name('sprints.update');
            Route::delete('sprints/{sprint}', 'destroy')->name('sprints.destroy');
            Route::post('sprints/{sprint}/start', 'start')->name('sprints.start');
            Route::post('sprints/{sprint}/complete', 'complete')->name('sprints.complete');
            Route::post('sprints/{sprint}/issues', 'addIssues')->name('sprints.issues.add');
            Route::delete('sprints/{sprint}/issues/{issue}', 'removeIssue')->name('sprints.issues.remove');
        });
    });

    // Files
    Route::controller(FileController::class)->group(function (): void {
        Route::get('files', 'index')->middleware('permission:files.view')->name('files.index');
        Route::post('files', 'store')->middleware(['permission:files.upload', 'throttle:uploads'])->name('files.store');
        Route::get('files/{file}/download', 'download')->middleware('permission:files.view')->name('files.download');
        Route::get('files/{file}/preview', 'preview')->middleware('permission:files.view')->name('files.preview');
        Route::delete('files/{file}', 'destroy')->middleware('permission:files.delete_own|files.delete_any')->name('files.destroy');
    });

    // Finance: client payments
    Route::controller(ClientPaymentController::class)->group(function (): void {
        Route::get('finance/summary', 'summary')->middleware('permission:finance.view')->name('finance.summary');
        Route::get('client-payments', 'index')->middleware('permission:finance.view')->name('payments.index');
        Route::get('projects/{project}/payments', 'forProject')->middleware('permission:finance.view')->name('payments.project');
        Route::post('projects/{project}/payments', 'store')->middleware('permission:client_payments.manage')->name('payments.store');
        Route::patch('client-payments/{payment}', 'update')->middleware('permission:client_payments.manage')->name('payments.update');
        Route::delete('client-payments/{payment}', 'destroy')->middleware('permission:client_payments.manage')->name('payments.destroy');
    });

    // Finance: salary requests
    Route::controller(SalaryRequestController::class)->group(function (): void {
        Route::get('salary-requests', 'index')->middleware('permission:salary.request|finance.view')->name('salary.index');
        Route::post('salary-requests', 'store')->middleware('permission:salary.request')->name('salary.store');
        Route::patch('salary-requests/{salaryRequest}', 'update')->middleware('permission:salary.request')->name('salary.update');
        Route::delete('salary-requests/{salaryRequest}', 'destroy')->middleware('permission:salary.request')->name('salary.destroy');
        Route::post('salary-requests/{salaryRequest}/approve', 'approve')->middleware('permission:salary.approve')->name('salary.approve');
        Route::post('salary-requests/{salaryRequest}/reject', 'reject')->middleware('permission:salary.approve')->name('salary.reject');
    });

    // Reports
    Route::get('reports/summary', [ReportController::class, 'summary'])->middleware('permission:reports.view')->name('reports.summary');
    Route::get('reports/export', [ReportController::class, 'export'])->middleware('permission:reports.export')->name('reports.export');

    // Admin: users
    Route::controller(UserController::class)->group(function (): void {
        Route::get('users', 'index')->middleware('permission:users.view')->name('users.index');
        Route::get('users/{user}', 'show')->middleware('permission:users.view')->name('users.show');
        Route::post('users', 'store')->middleware('permission:users.manage')->name('users.store');
        Route::patch('users/{user}', 'update')->middleware('permission:users.manage')->name('users.update');
        Route::post('users/{user}/password-reset-link', 'sendPasswordReset')->middleware('permission:users.manage')->name('users.password-reset');
    });

    // Admin: roles
    Route::controller(RoleController::class)->group(function (): void {
        Route::get('roles', 'index')->middleware('permission:roles.manage|users.manage')->name('roles.index');
        Route::get('permissions', 'permissions')->middleware('permission:roles.manage|users.manage')->name('permissions.index');
        Route::post('roles', 'store')->middleware('permission:roles.manage')->name('roles.store');
        Route::patch('roles/{role}', 'update')->middleware('permission:roles.manage')->name('roles.update');
        Route::delete('roles/{role}', 'destroy')->middleware('permission:roles.manage')->name('roles.destroy');
    });

    // Admin: system
    Route::prefix('admin')->name('admin.')->controller(SystemController::class)->group(function (): void {
        Route::get('stats', 'stats')->middleware('permission:users.manage')->name('stats');
        Route::post('export', 'export')->middleware('permission:system.export')->name('export');
        Route::post('reset-demo', 'resetDemo')->middleware('permission:system.reset_demo')->name('reset-demo');
    });
});
