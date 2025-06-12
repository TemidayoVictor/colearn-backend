<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

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

        $user = User::create([
            'name' => $request->firstName . ' ' . $request->lastName,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => 'Inactive',
        ]);

        return ResponseHelper::success(['user' => $user], "Account created successfully");
    }
}
