<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use App\Helpers\ModelHelper;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use App\Models\User;
use App\Models\Student;

class OnboardingController extends Controller
{
    //
    public function verifyOtp(Request $request) {

        $validator = Validator::make($request->all(), [
            'otp' => 'required',
            'userId' => 'required',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $user = ModelHelper::findOrFailWithCustomResponse(User::class, $request->userId, 'User not found', 'userId');

        if($user->email_verification_code === $request->otp) {
            // update email_verified_at
            $currentTime = Carbon::now()->format('Y-m-d');
            $user->update([
                'email_verified_at' => $currentTime
            ]);

            return ResponseHelper::success('OTP Verified Successfully');
        }

        else {
            return ResponseHelper::error('Invalid OTP');
        }
    }

    public function resendOtp(Request $request) {
        $validator = Validator::make($request->all(), [
            'userId' => 'required',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $user = ModelHelper::findOrFailWithCustomResponse(User::class, $request->userId, 'User not found', 'userId');

        // generate and update new email verification code
        $emailVerificationCode = random_int(100000, 999999);
        $user->update([
            'email_verification_code' => $emailVerificationCode,
        ]);

        // send new email

        return ResponseHelper::success('OTP Resent Successfully');
    }

    public function selectAccount(Request $request) {
        $validator = Validator::make($request->all(), [
            'userId' => 'required',
            'selected' => 'required',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $user = ModelHelper::findOrFailWithCustomResponse(User::class, $request->userId, 'User not found', 'userId');

        $selected = $request->selected;

        if($selected == 'student') {
            $student = Student::create([
                'user_id' => $request->userId
            ]);

            if($student) {
                // update the user type
                $user->update([
                    'type' => 'student',
                    'profile_progress' => '1',
                ]);

                return ResponseHelper::success('Student Account Created', ['user' => $user, 'student' => $student]);
            }
        }

    }
}
