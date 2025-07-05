<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\SignUpController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\OnboardingController;
use App\Http\Controllers\Api\V1\UtilitiesController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\ConsultantController;

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
        Route::post('/delete-course', [CourseController::class, 'deleteCourse']);

        // Modules
        Route::post('/upload-module', [CourseController::class, 'addModules']);
        Route::post('/edit-module', [CourseController::class, 'editModule']);
        Route::post('/get-module-details', [CourseController::class, 'getModule']);
        Route::post('/delete-module', [CourseController::class, 'deleteModule']);

        // Videos
        Route::post('/upload-video', [CourseController::class, 'uploadVideo']);
        Route::post('/edit-video', [CourseController::class, 'editVideo']);
        Route::post('/delete-video', [CourseController::class, 'deleteVideo']);

        // Resource
        Route::post('/upload-resource', [CourseController::class, 'uploadResource']);
        Route::post('/edit-resource', [CourseController::class, 'editResource']);
        Route::post('/delete-resource', [CourseController::class, 'deleteResource']);

        // Publish Course
        Route::post('/publish-resource', [CourseController::class, 'publishCourse']);

        // Consultant
        Route::post('/submit-schools', [ConsultantController::class, 'submitSchools']);
        Route::post('/edit-schools', [ConsultantController::class, 'editSchools']);

        Route::post('/submit-certs', [ConsultantController::class, 'submitCerts']);
        Route::post('/edit-certs', [ConsultantController::class, 'editCert']);

        Route::post('/submit-intro-video', [ConsultantController::class, 'submitIntroVideo']);

        Route::post('/submit-application', [ConsultantController::class, 'submitApplication']);

        Route::post('/create-consultant-account', [ConsultantController::class, 'createConsultantAccount']);

        Route::post('/set-availability', [ConsultantController::class, 'setAvailability']);

        Route::get('/get-all-consultants', [ConsultantController::class, 'getAllConsultants']);
        Route::post('/get-consultant', [ConsultantController::class, 'getConsultant']);

        Route::post('/get-sessions', [ConsultantController::class, 'getSessions']);
        Route::post('/get-sessions-consultant', [ConsultantController::class, 'getSessionsConsultant']);
        Route::post('/book-session', [ConsultantController::class, 'bookSession']);

        Route::post('/update-session-user', [ConsultantController::class, 'updateSessionUser']);
        Route::post('/update-session-consultant', [ConsultantController::class, 'updateSessionConsultant']);

        Route::post('/cancel-session-user', [ConsultantController::class, 'cancelSessionUser']);
        Route::post('/cancel-session-consultant', [ConsultantController::class, 'cancelSessionConsultant']);

        Route::post('/reschedule-session-consultant', [ConsultantController::class, 'rescheduleSessionConsultant']);
        Route::post('/approve-reschedule', [ConsultantController::class, 'approveReschedule']);
    });

    // Utilities
    Route::post('/get-countries', [UtilitiesController::class, 'countries']);
});