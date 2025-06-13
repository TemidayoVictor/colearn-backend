<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    //

    public function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        if (!Auth::attempt($validator->validated())) {
            return ResponseHelper::error('Invalid Credentials', [], 401);
        }

        return ResponseHelper::success("Login Successful", ['user' => Auth::user()]);
    }

    public function logout(Request $request) {
        Auth::guard('web')->logout(); // logs out the user
        $request->session()->invalidate(); // invalidates the session
        $request->session()->regenerateToken(); // regenerates the CSRF token
        return ResponseHelper::success("Logout Successful", []);
    }
}
