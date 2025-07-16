<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use App\Helpers\ModelHelper;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use App\Models\User;

class SettingsController extends Controller
{
    //
    public function changeEmail(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
            'email' => 'required|email|unique:users,email',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $check = User::where('email', $request->email)->first();
        if($check) {
            return ResponseHelper::error('Email already exists');
        }

        $user = User::where('id', $request->id)->first();

        $email = $request->email;
        $code = rand(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => $code,
                'created_at' => Carbon::now()
            ]
        );

        return ResponseHelper::success('Otp sent successfully');
    }

    public function verifyEmailCode(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'code' => 'required',
            'id' => 'required|exists:users,id',
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

        $user = User::where('id', $request->id)->first();

        $update = $user->update([
            'email' => $request->email,
        ]);

        // Delete reset record
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return ResponseHelper::success("Email updated successfully");
    }

    public function changePassword(Request $request) {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/'
            ],
            'id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $user = User::where('id', $request->id)->first();

        if (!Hash::check($request->current_password, $user->password)) {
            return ResponseHelper::error('Your current password is incorrect.');
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return ResponseHelper::success('Password updated successfully.');
    }

    public function deactivateAccount(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
            'reason' => 'required',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $user = User::where('id', $request->id)->first();

        $update = $user->update([
            'status' => 'Deactivated-By-User',
            'reason' => $request->reason,
        ]);

        return ResponseHelper::success('Account deactivated successfully.');
    }

    public function reactivateAccount(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
            'reason' => 'nullable',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $user = User::where('id', $request->id)->first();

        $reason = null;
        if($request->reason) {
            $reason = $request->reason;
        }

        $update = $user->update([
            'status' => 'Active',
            'reason' => $reason,
        ]);

        return ResponseHelper::success('Account deactivated successfully.');
    }
}
