<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

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

        $user = User::create([
            'first_name' => $request->firstName,
            'last_name' => $request->lastName,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => 'Inactive',
            'email_verification_code' => $emailVerificationCode,
        ]);

        // Log the user in (sets session cookie)
        Auth::login($user);

        // get all countries
        $countries = Country::all();
        $languages = Language::all();
        $preferences = Preferences::all();
        $categories = Category::all();

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
