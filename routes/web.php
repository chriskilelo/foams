<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Assets\AssetController;
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
});

Route::middleware(['auth', 'two_factor', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('regions', Admin\RegionController::class)->except('show');
        Route::resource('counties', Admin\CountyController::class)->except('show');
    });

require __DIR__.'/settings.php';
