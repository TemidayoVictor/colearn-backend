<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\SignUpController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\OnboardingController;

Route::prefix('v1')->group(function () {
    Route::post('/createAccount', [SignUpController::class, 'createAccount']);

    // authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        // Onboarding
        Route::post('/verify-otp-onboarding', [OnboardingController::class, 'verifyOtp']);
        Route::post('/resend-otp-onboarding', [OnboardingController::class, 'resendOtp']);
        Route::post('/select-account', [OnboardingController::class, 'selectAccount']);
    });
});