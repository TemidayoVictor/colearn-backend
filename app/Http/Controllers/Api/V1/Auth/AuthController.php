<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\Models\Country;
use App\Models\Language;
use App\Models\Preferences;
use App\Models\Category;

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

        // get all countries
        $countries = Country::all();
        $languages = Language::all();
        $preferences = Preferences::all();
        $categories = Category::all();

        return ResponseHelper::success("Login Successful",
        [
            'user' => Auth::user(),
            'countries' => $countries,
            'languages' => $languages,
            'preferences' => $preferences,
            'categories' => $categories,
        ]);
    }

    public function logout(Request $request) {
        Auth::guard('web')->logout(); // logs out the user
        $request->session()->invalidate(); // invalidates the session
        $request->session()->regenerateToken(); // regenerates the CSRF token
        return ResponseHelper::success("Logout Successful", []);
    }
}
