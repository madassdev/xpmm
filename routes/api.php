<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Bills\AirtimeController;
use App\Http\Controllers\Bills\ElectricityController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/bills/airtime', [AirtimeController::class, 'purchase'])->name('api.bills.airtime');
    Route::post('/bills/electricity/validate', [ElectricityController::class, 'validateCustomer']);
    Route::post('/bills/electricity', [ElectricityController::class, 'purchase']);
    Route::get('/bills/electricity/{reference}/status', [ElectricityController::class, 'status']);
});

Route::post('/auth/login',  [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Protected APIs (require Bearer token)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', fn (Request $r) => $r->user());
    Route::post('/bills/airtime', [AirtimeController::class,'purchase']);
    Route::post('/bills/electricity/validate', [ElectricityController::class, 'validateCustomer']);
    Route::post('/bills/electricity', [ElectricityController::class, 'purchase']);
    Route::get('/bills/electricity/{reference}/status', [ElectricityController::class, 'status']);
});