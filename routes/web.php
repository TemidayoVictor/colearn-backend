<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\Auth\SignUpController;
use App\Http\Controllers\Api\V1\Auth\AuthMiddlewareController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\CourseController;

Route::get('/', function () {
    return view('welcome');
});

// route to verify if user is logged in
// Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/login', [AuthController::class, 'login']);

Route::get('/web-data', [UserController::class, 'webData']);

Route::post('/instructor-data-web', [UserController::class, 'instructorDataWeb']);

Route::post('/course-search', [CourseController::class, 'search']);

Route::middleware('auth:sanctum')->group(function () {
    // authenticate user
    Route::get('/user', [AuthMiddlewareController::class, 'authenticateUser']);
    Route::get('/user-instructor', [AuthMiddlewareController::class, 'authenticateUserInstructor']);
    Route::get('/user-student', [AuthMiddlewareController::class, 'authenticateUserStudent']);

    // logout
    Route::post('/logout', [AuthController::class, 'logout']);
});

