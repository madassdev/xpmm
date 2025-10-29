<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Bills\AirtimeController;
use App\Http\Controllers\Bills\BettingController;
use App\Http\Controllers\Bills\CableController;
use App\Http\Controllers\Bills\ElectricityController;
use App\Http\Controllers\Bills\InternetController;
use App\Http\Controllers\Bills\MobileDataController;
use App\Http\Controllers\Settings\BankAccountController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/bills/airtime', [AirtimeController::class, 'purchase'])->name('api.bills.airtime');
});

Route::post('/auth/login',  [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Protected APIs (require Bearer token)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', fn (Request $r) => $r->user());
    Route::post('/bills/airtime', [AirtimeController::class,'purchase']);
    Route::post('/bills/data', [MobileDataController::class, 'purchase']);
    Route::get('/bills/data/plans', [MobileDataController::class, 'getPlans']);

    Route::get('/bills/internet/plans', [InternetController::class, 'plans']);
    Route::post('/bills/internet', [InternetController::class, 'purchase']);

    Route::get('/bills/tv/plans', [CableController::class, 'plans']);
    Route::post('/bills/tv/validate', [CableController::class, 'validateCustomer']);
    Route::post('/bills/tv', [CableController::class, 'purchase']);

    Route::post('/bills/electricity/validate', [ElectricityController::class, 'validateCustomer']);
    Route::post('/bills/electricity', [ElectricityController::class, 'purchase']);

    Route::post('/bills/betting', [BettingController::class, 'purchase']);
    Route::get('/settings/bank-accounts', [BankAccountController::class, 'index']);
    Route::post('/settings/bank-accounts', [BankAccountController::class, 'store']);
    Route::post('/settings/bank-accounts/verify', [BankAccountController::class, 'verify']);
    Route::put('/settings/bank-accounts/{bankAccount}', [BankAccountController::class, 'update']);
    Route::delete('/settings/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy']);
});
