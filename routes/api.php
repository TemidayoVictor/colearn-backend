<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\SignUpController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\OnboardingController;
use App\Http\Controllers\Api\V1\UtilitiesController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\ConsultantController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\BlogController;

Route::prefix('v1')->group(function () {
    // Sign up
    Route::post('/createAccount', [SignUpController::class, 'createAccount']);

    // forgot password
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/verify-reset-code', [AuthController::class, 'verifyResetCode']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::post('/add-admin', [AdminController::class, 'addAdmin']);

    // authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        // Onboarding
        Route::post('/verify-otp-onboarding', [OnboardingController::class, 'verifyOtp']);
        Route::post('/resend-otp-onboarding', [OnboardingController::class, 'resendOtp']);
        Route::post('/select-account', [OnboardingController::class, 'selectAccount']);

        Route::post('/submit-details', [OnboardingController::class, 'submitDetails']);
        Route::post('/edit-details', [OnboardingController::class, 'editDetails']);
        Route::post('/add-preferences', [OnboardingController::class, 'addPreferences']);
        Route::post('/edit-name', [OnboardingController::class, 'editName']);
        Route::post('/edit-professional-data', [OnboardingController::class, 'editProfessionalData']);

        Route::post('/submit-professional-details', [OnboardingController::class, 'submitProfessionalDetails']);
        Route::post('/submit-experiences', [OnboardingController::class, 'submitExperiences']);
        Route::post('/add-experiences', [OnboardingController::class, 'addExperiences']);
        Route::post('/edit-experience', [OnboardingController::class, 'editExperience']);
        Route::post('/delete-experience', [OnboardingController::class, 'deleteExperience']);

        // Instructor Authenticated Route

        // Instructor Details
        Route::post('/instructor-experiences', [OnboardingController::class, 'instructorExperiences']);

        // Courses
        Route::get('/all-courses', [CourseController::class, 'allCourses']);

        Route::post('/upload-course', [CourseController::class, 'uploadCourse']);
        Route::post('/edit-course', [CourseController::class, 'editCourse']);
        Route::post('/all-instructors-courses', [CourseController::class, 'allInstructorsCourses']);
        Route::post('/get-course-details', [CourseController::class, 'getCourse']);
        Route::post('/get-course-details-edit', [CourseController::class, 'getCourseEdit']);
        Route::post('/delete-course', [CourseController::class, 'deleteCourse']);
        Route::post('/get-course-student', [CourseController::class, 'getCourseStudent']);

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

        // Cart
        Route::post('/get-cart', [CourseController::class, 'getCart']);
        Route::post('/add-to-cart', [CourseController::class, 'addToCart']);
        Route::post('/remove-from-cart', [CourseController::class, 'removeFromCart']);

        // Coupon
        Route::post('/add-coupon', [CourseController::class, 'addCoupon']);
        Route::post('/create-coupon', [CourseController::class, 'createCoupon']);
        Route::post('/get-coupons', [CourseController::class, 'getCoupons']);
        Route::post('/delete-coupon', [CourseController::class, 'deleteCoupon']);

        // Checkout
        Route::post('/checkout-calculate', [CourseController::class, 'checkoutCalculate']);

        // Enroll
        Route::post('/enroll', [CourseController::class, 'enroll']);
        Route::post('/enrolled-courses', [CourseController::class, 'enrolledCourses']);

        // Course progress
        Route::post('/watch-video', [CourseController::class, 'watchVideo']);
        Route::post('/mark-video-as-complete', [CourseController::class, 'markVideoAsComplete']);

        // Reviews
        Route::post('/review', [CourseController::class, 'review']);

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

        Route::get('/get-all-sessions', [ConsultantController::class, 'getAllSessions']);
        Route::post('/get-sessions', [ConsultantController::class, 'getSessions']);
        Route::post('/get-sessions-consultant', [ConsultantController::class, 'getSessionsConsultant']);
        Route::post('/book-session', [ConsultantController::class, 'bookSession']);

        Route::post('/update-session-user', [ConsultantController::class, 'updateSessionUser']);
        Route::post('/update-session-consultant', [ConsultantController::class, 'updateSessionConsultant']);

        Route::post('/cancel-session-user', [ConsultantController::class, 'cancelSessionUser']);
        Route::post('/cancel-session-consultant', [ConsultantController::class, 'cancelSessionConsultant']);
        Route::post('/cancel-session-admin', [ConsultantController::class, 'cancelSessionAdmin']);

        Route::post('/reschedule-session-consultant', [ConsultantController::class, 'rescheduleSessionConsultant']);
        Route::post('/approve-reschedule', [ConsultantController::class, 'approveReschedule']);

        Route::post('/update-payment', [ConsultantController::class, 'updatePaymentStatus']);
        Route::post('/update-session-status', [ConsultantController::class, 'updateSessionStatus']);

        // Settings
        Route::post('/change-email', [SettingsController::class, 'changeEmail']);
        Route::post('/verify-email-code', [SettingsController::class, 'verifyEmailCode']);
        Route::post('/change-password', [SettingsController::class, 'changePassword']);
        Route::post('/deactivate-account', [SettingsController::class, 'deactivateAccount']);
        Route::post('/reactivate-account', [SettingsController::class, 'reactivateAccount']);

        // User Routes
        Route::post('/get-user-transactions', [UserController::class, 'getUserTransactions']);
        Route::post('/student-dashboard', [UserController::class, 'studentDashboard']);
        Route::post('/user-profile', [UserController::class, 'userProfile']);
        Route::post('/instructor-dashboard', [UserController::class, 'instructorDashboard']);

        // Admin Routes
        Route::get('/admin-dashboard', [AdminController::class, 'adminDashboard']);
        Route::get('/admin-course', [AdminController::class, 'adminCourses']);

        Route::post('/credit-wallet', [AdminController::class, 'creditWallet']);
        Route::post('/debit-wallet', [AdminController::class, 'debitWallet']);
        Route::post('/withdraw-funds', [AdminController::class, 'withdrawFunds']);

        Route::post('/approve-withdrawal', [AdminController::class, 'approveWithdrawal']);
        Route::post('/reject-withdrawal', [AdminController::class, 'rejectWithdrawal']);

        Route::post('/admin-credit', [AdminController::class, 'adminCredit']);
        Route::post('/admin-debit', [AdminController::class, 'adminDebit']);

        Route::post('/all-transactions', [AdminController::class, 'allTransactions']);
        Route::post('/admin-transactions', [AdminController::class, 'adminTransactions']);
        Route::post('/admin-credit-transactions', [AdminController::class, 'adminCreditTransactions']);
        Route::post('/admin-debit-transactions', [AdminController::class, 'adminDebitTransactions']);

        Route::get('/all-users-admin', [AdminController::class, 'allUsers']);
        Route::post('/get-user-details', [AdminController::class, 'getUserDetails']);

        Route::get('/all-unapproved-consultants', [AdminController::class, 'allUnapprovedConsultants']);
        Route::post('/approve-consultant', [AdminController::class, 'approveConsultant']);
        Route::post('/decline-consultant', [AdminController::class, 'declineConsultant']);

        Route::post('/update-general-settings', [AdminController::class, 'updateGeneralSettings']);

        // Blogs
        Route::post('/create-blog', [BlogController::class, 'createBlog']);
        Route::post('/edit-blog', [BlogController::class, 'editBlog']);
        Route::post('/delete-blog', [BlogController::class, 'deleteBlog']);
        Route::post('/get-all-blogs', [BlogController::class, 'getAllBlogs']);

    });

    // Utilities
    Route::post('/get-countries', [UtilitiesController::class, 'countries']);
    Route::get('/download-resource/{filename}/{title}', [UtilitiesController::class, 'downloadResource']);
});