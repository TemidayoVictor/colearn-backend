<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use App\Helpers\ModelHelper;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

use App\Models\User;
use App\Models\Student;
use App\Models\Instructor;

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

        elseif($selected == 'instructor') {
            $instructor = Instructor::create([
                'user_id' => $request->userId
            ]);

            if($instructor) {
                // update the user type
                $user->update([
                    'type' => 'instructor',
                    'profile_progress' => '1',
                ]);

                return ResponseHelper::success('Instructor Account Created', ['user' => $user, 'instructor' => $instructor]);
            }
        }

        return ResponseHelper::error('Invalid account type', [], 404);

    }

    public function submitStudentDetails(Request $request) {
        $validator = Validator::make($request->all(), [
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'gender' => 'required|string|in:Male,Female,Other',
            'country' => 'required|string',
            'phone' => 'required|string',
            'languages' => 'required|array',
            'languages.*' => 'string',
            'userId' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $user = ModelHelper::findOrFailWithCustomResponse(User::class, $request->userId, 'User not found', 'userId');

        $student = $user->student;

        if (!$student) {
            return ResponseHelper::error('Student profile not found');
        }

        // update details in student table
        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('uploads/profile_photos', 'public');
            $student->profile_photo = $path;
        }

        $student->gender = $request->gender;
        $student->languages = $request->languages;
        $student->country = $request->country;
        $student->phone = $request->phone;
        $student->save();

        // update user progress
        $user->profile_progress = '2';
        $user->country_phone_code = $request->country_phone_code;
        $user->country_iso = $request->country_iso;
        $user->country_iso3 = $request->country_iso3;
        $user->save();

        return ResponseHelper::success('Details Updated Successfully', ['user' => $user, 'student' => $student]);

    }

    public function addPreferences(Request $request) {
        $validator = Validator::make($request->all(), [
            'preferences' => 'nullable|array',
            'preferences.*' => 'string',
            'userId' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $user = ModelHelper::findOrFailWithCustomResponse(User::class, $request->userId, 'User not found', 'userId');

        $user->preferences = $request->preferences;
        $user->profile_progress = 'completed';
        $user->save();

        return ResponseHelper::success('Details Updated Successfully', ['user' => $user]);
    }
}
