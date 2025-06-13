<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\SignUpController;
use App\Http\Controllers\Api\V1\Auth\AuthController;

Route::get('/test', [SignUpController::class, 'test']);

Route::prefix('v1')->group(function () {
    Route::post('/createAccount', [SignUpController::class, 'createAccount']);

    Route::post('/login', [AuthController::class, 'login'])->middleware(['web', 'auth:sanctum']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware(['web', 'auth:sanctum']);
});