<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Assets\AssetController;
use App\Http\Controllers\Assets\StatusLogController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified', 'two_factor', 'region.scope', 'not_public_role'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::middleware(['auth', 'two_factor', 'region.scope'])->group(function () {
    Route::resource('assets', AssetController::class);
    Route::patch('assets/{asset}/assign', [AssetController::class, 'assign'])->name('assets.assign');

    Route::get('status-logs', [StatusLogController::class, 'index'])->name('status-logs.index');
    Route::get('assets/{asset}/status-logs/create', [StatusLogController::class, 'create'])->name('assets.status-logs.create');
    Route::post('assets/{asset}/status-logs', [StatusLogController::class, 'store'])->name('assets.status-logs.store');
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
