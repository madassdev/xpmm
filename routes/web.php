<?php

use App\Http\Controllers\Bills\AirtimeController;
use App\Http\Controllers\BillsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
// use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Settings\SettingsController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth'])->name('dashboard');
Route::get('/bills', [BillsController::class, 'index'])->middleware(['auth'])->name('bills.index');

 

Route::middleware('auth')->group(function () {
    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');

    // Profile
    Route::patch('/settings/profile', [ProfileController::class, 'update'])
        ->name('settings.profile.update');
    Route::post('/settings/profile/avatar', [ProfileController::class, 'updateAvatar'])
        ->name('settings.profile.avatar');
    // Security
    Route::post('/settings/security/change-password', [SecurityController::class, 'changePassword'])
        ->name('settings.security.change-password');
    Route::post('/settings/security/reset-pin', [SecurityController::class, 'resetPin'])
        ->name('settings.security.reset-pin');
    Route::post('/settings/security/twofa', [SecurityController::class, 'updateTwoFactor'])
        ->name('settings.security.twofa');
    Route::post('/settings/security/trusted-device', [SecurityController::class, 'updateTrustedDevice'])
        ->name('settings.security.trusted-device');


    Route::post('/bills/airtime', [AirtimeController::class,'purchase']);

});

require __DIR__.'/auth.php';
