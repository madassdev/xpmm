<?php

use App\Http\Controllers\Admin\AdminPageController;
use App\Http\Controllers\Admin\CryptoAssetController;
use App\Http\Controllers\Admin\GiftcardController as AdminGiftcardController;
use App\Http\Controllers\Admin\GiftcardTransactionController;
use App\Http\Controllers\Bills\AirtimeController;
use App\Http\Controllers\Bills\MobileDataController;
use App\Http\Controllers\BillsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GiftcardController;
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

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/wallets', [DashboardController::class, 'wallets'])->name('wallets.index');
    Route::get('/cards', [DashboardController::class, 'cards'])->name('cards.index');
    Route::get('/referrals', [DashboardController::class, 'referrals'])->name('referrals.index');
    Route::get('/transactions', [DashboardController::class, 'transactions'])->name('transactions.index');
    Route::get('/transfer', [DashboardController::class, 'transfer'])->name('transfer.index');
    Route::get('/betting-topup', [DashboardController::class, 'betting'])->name('betting.index');

    Route::get('/bills', [BillsController::class, 'index'])->name('bills.index');
    Route::get('/gift-cards', [GiftcardController::class, 'index'])->name('giftcards.index');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', AdminPageController::class)->name('overview')->defaults('section', 'overview');
        Route::get('/transactions', AdminPageController::class)->name('transactions')->defaults('section', 'transactions');
        Route::get('/cards', AdminPageController::class)->name('cards')->defaults('section', 'cards');
        Route::get('/bills-management', AdminPageController::class)->name('bills')->defaults('section', 'bills-management');
        Route::get('/virtual-cards', AdminPageController::class)->name('virtual-cards')->defaults('section', 'virtual-cards');
        Route::get('/crypto-wallets', AdminPageController::class)->name('crypto-wallets')->defaults('section', 'crypto-wallets');
        Route::get('/users', AdminPageController::class)->name('users')->defaults('section', 'users');
        Route::get('/kyc', AdminPageController::class)->name('kyc')->defaults('section', 'kyc');
        Route::get('/settings', AdminPageController::class)->name('settings')->defaults('section', 'settings');
        
        Route::resource('giftcards', AdminGiftcardController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['giftcards' => 'giftcard']);

        Route::resource('crypto-exchange', CryptoAssetController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['crypto-exchange' => 'cryptoAsset']);

        Route::post('/crypto-exchange/{cryptoAsset}', [CryptoAssetController::class, 'update'])->name('crypto-exchange.upd');

        Route::get('/giftcard-transactions', [GiftcardTransactionController::class, 'index'])->name('giftcard-transactions.index');
        Route::post('/giftcard-transactions/{transaction}/approve', [GiftcardTransactionController::class, 'approve'])->name('giftcard-transactions.approve');
        Route::post('/giftcard-transactions/{transaction}/reject', [GiftcardTransactionController::class, 'reject'])->name('giftcard-transactions.reject');
    });

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
    Route::post('/bills/data', [MobileDataController::class,'purchase']);
    Route::get('/bills/data/plans', [MobileDataController::class, 'getPlans']); // ?network=mtn
});

// Public giftcard listing
Route::get('/giftcards', [GiftcardController::class, 'list']);

// Protected giftcard transactions
Route::middleware('auth')->group(function () {
    Route::post('/giftcards/sell', [GiftcardController::class, 'sell']);
    Route::post('/giftcards/buy', [GiftcardController::class, 'buy']);
});

require __DIR__.'/auth.php';
