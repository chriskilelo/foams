<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Assets\AssetController;
use App\Http\Controllers\Assets\StatusLogController;
use App\Http\Controllers\Assets\UptimeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Issues\AttachmentController;
use App\Http\Controllers\Issues\IssueActivityController;
use App\Http\Controllers\Issues\IssueController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified', 'two_factor', 'region.scope', 'not_public_role'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

Route::middleware(['auth', 'two_factor', 'region.scope'])->group(function () {
    Route::resource('assets', AssetController::class);
    Route::patch('assets/{asset}/assign', [AssetController::class, 'assign'])->name('assets.assign');
    Route::get('assets/{asset}/uptime', [UptimeController::class, 'show'])->name('assets.uptime');

    Route::get('status-logs', [StatusLogController::class, 'index'])->name('status-logs.index');
    Route::get('assets/{asset}/status-logs/create', [StatusLogController::class, 'create'])->name('assets.status-logs.create');
    Route::post('assets/{asset}/status-logs', [StatusLogController::class, 'store'])->name('assets.status-logs.store');

    // Issues
    Route::resource('issues', IssueController::class)->only(['index', 'show', 'create', 'store']);
    Route::patch('issues/{issue}/status', [IssueController::class, 'updateStatus'])->name('issues.update-status');
    Route::post('issues/{issue}/escalate', [IssueController::class, 'escalate'])->name('issues.escalate');
    Route::post('issues/{issue}/resolve', [IssueController::class, 'resolve'])->name('issues.resolve');
    Route::post('issues/{issue}/close', [IssueController::class, 'close'])->name('issues.close');

    // Issue comments (append-only)
    Route::post('issues/{issue}/activities', [IssueActivityController::class, 'store'])->name('issues.activities.store');

    // Attachments
    Route::post('issues/{issue}/attachments', [AttachmentController::class, 'store'])->name('issues.attachments.store');
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'show'])->name('attachments.show');
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');
});

Route::middleware(['auth', 'two_factor', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('regions', Admin\RegionController::class)->except('show');
        Route::resource('counties', Admin\CountyController::class)->except('show');
        Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::patch('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
    });

require __DIR__.'/settings.php';
