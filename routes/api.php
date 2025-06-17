<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\SignUpController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\OnboardingController;
use App\Http\Controllers\Api\V1\UtilitiesController;

Route::prefix('v1')->group(function () {
    // Sign up
    Route::post('/createAccount', [SignUpController::class, 'createAccount']);

    // authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        // Onboarding
        Route::post('/verify-otp-onboarding', [OnboardingController::class, 'verifyOtp']);
        Route::post('/resend-otp-onboarding', [OnboardingController::class, 'resendOtp']);
        Route::post('/select-account', [OnboardingController::class, 'selectAccount']);
    });

    // Utilities
    Route::post('/get-countries', [UtilitiesController::class, 'countries']);
});