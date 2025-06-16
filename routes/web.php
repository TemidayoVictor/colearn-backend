<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\Auth\SignUpController;
use App\Http\Controllers\Api\V1\Auth\AuthMiddlewareController;
use App\Http\Controllers\Api\V1\Auth\AuthController;

Route::get('/', function () {
    return view('welcome');
});

// route to verify if user is logged in
// Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // authenticate user
    Route::get('/user', [AuthMiddlewareController::class, 'authenticateUser']);

    // logout
    Route::post('/logout', [AuthController::class, 'logout']);
});

