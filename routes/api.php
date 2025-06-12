<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\SignUpController;

Route::get('/test', [SignUpController::class, 'test']);

Route::prefix('v1')->group(function () {
    Route::post('/createAccount', [SignUpController::class, 'createAccount']);
});