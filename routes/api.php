<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\SignUpController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\OnboardingController;
use App\Http\Controllers\Api\V1\UtilitiesController;
use App\Http\Controllers\Api\V1\CourseController;

Route::prefix('v1')->group(function () {
    // Sign up
    Route::post('/createAccount', [SignUpController::class, 'createAccount']);

    // authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        // Onboarding
        Route::post('/verify-otp-onboarding', [OnboardingController::class, 'verifyOtp']);
        Route::post('/resend-otp-onboarding', [OnboardingController::class, 'resendOtp']);
        Route::post('/select-account', [OnboardingController::class, 'selectAccount']);

        Route::post('/submit-details', [OnboardingController::class, 'submitDetails']);
        Route::post('/add-preferences', [OnboardingController::class, 'addPreferences']);

        Route::post('/submit-professional-details', [OnboardingController::class, 'submitProfessionalDetails']);
        Route::post('/submit-experiences', [OnboardingController::class, 'submitExperiences']);

        // Instructor Authenticated Route

        // Courses
        Route::post('/upload-course', [CourseController::class, 'uploadCourse']);
        Route::post('/edit-course', [CourseController::class, 'editCourse']);
        Route::post('/all-courses', [CourseController::class, 'allCourses']);
        Route::post('/get-course-details', [CourseController::class, 'getCourse']);
        Route::post('/get-course-details-edit', [CourseController::class, 'getCourseEdit']);

        // Modules
        Route::post('/upload-module', [CourseController::class, 'addModules']);
        Route::post('/edit-module', [CourseController::class, 'editModule']);
        Route::post('/get-module-details', [CourseController::class, 'getModule']);

        // Videos
        Route::post('/upload-video', [CourseController::class, 'uploadVideo']);
        Route::post('/edit-video', [CourseController::class, 'editVideo']);

        // Resource
        Route::post('/upload-resource', [CourseController::class, 'uploadResource']);
        Route::post('/edit-resource', [CourseController::class, 'editResource']);
    });

    // Utilities
    Route::post('/get-countries', [UtilitiesController::class, 'countries']);
});