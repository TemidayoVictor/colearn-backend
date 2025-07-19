<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Stevebauman\Location\Facades\Location;
use App\Helpers\TimeZoneHelper;
use App\Helpers\ResponseHelper;

use App\Models\User;

class AdminController extends Controller
{
    //
    public function addAdmin(Request $request) {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'role' => 'required',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $ip = '102.89.39.111'; // or use a test IP like '102.89.39.111 $request->ip()'
        $position = Location::get($ip);

        $timezone = null;
        if ($position && $position->countryCode) {
            $timezone = TimezoneHelper::mapCountryToTimezone($position->countryCode);
        }

        $emailVerificationCode = random_int(100000, 999999);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => 'admin',
            'email_verification_code' => $emailVerificationCode,
            'timezone' => $timezone,
            'status' => 'Active',
            'profile_progress' => 'completed',
        ]);

        return ResponseHelper::success("Account created successfully");

    }
}
