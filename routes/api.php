<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::put('/customers/{id}', [CustomerController::class, 'update']);
    Route::post('/customers/{id}/deposit', [CustomerController::class, 'deposit']);
    Route::post('/customers/{id}/withdraw', [CustomerController::class, 'withdraw']);
    Route::post('/customers/{id}/transfer', [CustomerController::class, 'transfer']);
    Route::get('/customers/export', [CustomerController::class, 'exportTransactions']);
});

Route::middleware(['auth:sanctum', 'role:pimpinan'])->group(function () {
    Route::apiResource('/users', UserController::class);

    Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/{id}', [CustomerController::class, 'show']);
    Route::get('/customers/{id}/transactions', [CustomerController::class, 'transactions']);

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/token/refresh', [AuthController::class, 'refresh']);
