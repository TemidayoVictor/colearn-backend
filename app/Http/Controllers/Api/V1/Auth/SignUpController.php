<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Stevebauman\Location\Facades\Location;
use App\Helpers\TimeZoneHelper;
use App\Mail\EmailVerification;
use Illuminate\Support\Facades\Mail;

use App\Models\User;
use App\Models\Country;
use App\Models\Language;
use App\Models\Preferences;
use App\Models\Category;

class SignUpController extends Controller
{
    public function createAccount(Request $request) {
        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/'
            ],
            'confirmPassword' => 'required|same:password'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error('Validation error: Kindly fill in all fields', $validator->errors(), 422);
        }

        $emailVerificationCode = random_int(100000, 999999);
        // get and update the timezone based on the user's IP address
        $ip = '102.89.39.111'; // or use a test IP like '102.89.39.111 $request->ip()'
        $position = Location::get($ip);

        $timezone = null;
        if ($position && $position->countryCode) {
            $timezone = TimezoneHelper::mapCountryToTimezone($position->countryCode);
        }

        $user = User::create([
            'first_name' => $request->firstName,
            'last_name' => $request->lastName,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => 'Inactive',
            'email_verification_code' => $emailVerificationCode,
            'timezone' => $timezone,
            'status' => 'Active',
        ]);

        // Log the user in (sets session cookie)
        Auth::login($user);

        // get all countries
        $countries = Country::all();
        $languages = Language::all();
        $preferences = Preferences::all();
        $categories = Category::all();

        // Send email verification
        Mail::to($request->email)->send(new EmailVerification($emailVerificationCode));

        return ResponseHelper::success("Account created successfully",
        [
            'user' => $user,
            'countries' => $countries,
            'languages' => $languages,
            'preferences' => $preferences,
            'categories' => $categories,

        ]);
    }
}
