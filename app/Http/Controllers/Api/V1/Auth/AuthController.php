<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Stevebauman\Location\Facades\Location;
use App\Helpers\TimeZoneHelper;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

use App\Models\User;
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

    public function forgotPassword(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:users,email',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $email = $request->email;
        $code = rand(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => $code,
                'created_at' => Carbon::now()
            ]
        );

        return ResponseHelper::success("Otp Sent Successfully");
    }

    public function verifyResetCode(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:users,email',
            'code' => 'required',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $record = DB::table('password_reset_tokens')
        ->where('email', $request->email)
        ->where('token', $request->code)
        ->first();

        if (!$record) {
            return ResponseHelper::error('Invalid or expired code', [], 401);
        }

        return ResponseHelper::success("Code verified successfully");
    }

    public function resetPassword(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:users,email',
            'code' => 'required',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/'
            ],
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $record = DB::table('password_reset_tokens')
        ->where('email', $request->email)
        ->where('token', $request->code)
        ->first();

        if (!$record) {
            return ResponseHelper::error('Invalid or expired code', [], 401);
        }

        // Update password
        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password)
        ]);

        // Delete reset record
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return ResponseHelper::success("Password reset successfully");
    }
}
