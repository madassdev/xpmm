<?php

use App\Http\Controllers\Admin\WalletFundingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/wallets', [WalletFundingController::class, 'index'])->name('wallets.index');
        Route::get('/wallets/fund', [WalletFundingController::class, 'create'])->name('wallets.fund');
        Route::post('/wallets/fund', [WalletFundingController::class, 'store'])->name('wallets.fund.store');
    });
