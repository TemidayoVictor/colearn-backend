<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Stevebauman\Location\Facades\Location;
use App\Helpers\TimeZoneHelper;

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

        // get and update the timezone based on the user's IP address
        $ip = '102.89.39.111'; // or use a test IP like '102.89.39.111 $request->ip()'
        $position = Location::get($ip);

        if ($position && $position->countryCode) {
            $timezone = TimezoneHelper::mapCountryToTimezone($position->countryCode);

            Auth::user()->update(['timezone' => $timezone]);
        }

        else {
            // Fallback timezone if location cannot be determined
            $timezone = 'America/New_York';
            Auth::user()->update(['timezone' => $timezone]);
        }

        // get all required data after successful login
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
