<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;

use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    //
    public function logout(Request $request) {
        Auth::guard('web')->logout(); // logs out the user
        $request->session()->invalidate(); // invalidates the session
        $request->session()->regenerateToken(); // regenerates the CSRF token
        return ResponseHelper::success([], "Logout Successful");
    }
}
