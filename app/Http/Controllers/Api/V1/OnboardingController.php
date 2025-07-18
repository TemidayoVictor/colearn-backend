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
use App\Models\Experience;

class OnboardingController extends Controller
{
    //
    public function verifyOtp(Request $request) {

        $validator = Validator::make($request->all(), [
            'otp' => 'required',
            'userId' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $user = User::where('id', $request->userId)->first();

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
            'userId' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $user = User::where('id', $request->userId)->first();

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
            'userId' => 'required|exists:users,id',
            'selected' => 'required',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $user = User::where('id', $request->userId)->first();

        $selected = $request->selected;

        if($selected == 'student') {
            // check
            $check = Student::where('user_id', $request->userId)->first();

            if(!$check) {
                $student = Student::create([
                    'user_id' => $request->userId,
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

            return ResponseHelper::error('Existing User', [], 422);
        }

        elseif($selected == 'instructor') {
            // check
            $check = Instructor::where('user_id', $request->userId)->first();

            if(!$check) {
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

            return ResponseHelper::error('Existing User', [], 422);
        }

        return ResponseHelper::error('Invalid account type', [], 404);

    }

    public function submitDetails(Request $request) {
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

        $user = User::where('id', $request->userId)->first();
        $userTypeFetch = $user->type;

        if($userTypeFetch == 'student') {
            $userType = $user->student;
        }

        elseif($userTypeFetch == 'instructor') {
            $userType = $user->instructor;
        }

        if (!$userType) {
            return ResponseHelper::error('Profile not found');
        }

        // update details in student table
        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('uploads/profile_photos', 'public');
            $userType->profile_photo = $path;
        }

        $userType->gender = $request->gender;
        $userType->languages = $request->languages;
        $userType->country = $request->country;
        $userType->phone = $request->phone;
        $userType->save();

        // update user progress
        $user->profile_progress = '2';
        $user->country_phone_code = $request->country_phone_code;
        $user->country_iso = $request->country_iso;
        $user->country_iso3 = $request->country_iso3;
        $user->profile_photo = $path;
        $user->save();

        return ResponseHelper::success('Details Updated Successfully', ['user' => $user, $userTypeFetch => $userType]);

    }

    public function editDetails(Request $request) {
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

        $user = User::where('id', $request->userId)->first();
        $userTypeFetch = $user->type;

        if($userTypeFetch == 'student') {
            $userType = $user->student;
        }

        elseif($userTypeFetch == 'instructor') {
            $userType = $user->instructor;
        }

        if (!$userType) {
            return ResponseHelper::error('Profile not found');
        }

        $path = $userType->profile_photo;
        // update details in student table
        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('uploads/profile_photos', 'public');
        }

        $userType->gender = $request->gender;
        $userType->languages = $request->languages;
        $userType->country = $request->country;
        $userType->phone = $request->phone;
        $userType->profile_photo = $path;
        $userType->save();

        // update user progress
        $user->country_phone_code = $request->country_phone_code;
        $user->country_iso = $request->country_iso;
        $user->country_iso3 = $request->country_iso3;
        $user->profile_photo = $path;
        $user->save();

        return ResponseHelper::success('Details Updated Successfully', ['user' => $user, $userTypeFetch => $userType]);

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

        $user = User::where('id', $request->userId)->first();
        $userTypeFetch = $user->type;

        if($userTypeFetch == "instructor") {
            $userType = $user->instructor;
            $userType->disciplines = $request->preferences;
            $user->profile_progress = 'completed';
            $user->save();
            $userType->save();
            return ResponseHelper::success('Details Updated Successfully', ['user' => $user]);
        }

        $user->preferences = $request->preferences;
        $user->profile_progress = 'completed';
        $user->save();

        return ResponseHelper::success('Details Updated Successfully', ['user' => $user]);
    }

    public function editName(Request $request) {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'userId' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $user = User::where('id', $request->userId)->first();

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->save();

        return ResponseHelper::success('Details Updated Successfully', ['user' => $user]);
    }

    public function submitProfessionalDetails(Request $request) {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'headline' => 'required|string',
            'category' => 'required|string',
            'bio' => 'required|string',
            'userId' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $user = User::where('id', $request->userId)->first();
        $userTypeFetch = $user->type;

        if($userTypeFetch != "instructor") {
            return ResponseHelper::error('Profile not found');
        }

        $userType = $user->instructor;

        $userType->title = $request->title;
        $userType->professional_headline = $request->headline;
        $userType->category = $request->category;
        $userType->bio = $request->bio;
        $userType->linkedin_url = $request->linkedin;
        $userType->youtube_url = $request->youtube;
        $userType->twitter_url = $request->twitter;
        $userType->website = $request->website;
        $userType->save();

        $user->profile_progress = '3';
        $user->save();

        return ResponseHelper::success('Details Updated Successfully', ['user' => $user, $userTypeFetch => $userType]);

    }

    public function editProfessionalData(Request $request) {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:users,id',
            'title' => 'required|string',
            'headline' => 'required|string',
            'category' => 'required|string',
            'bio' => 'required|string',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $type = $request->type;
        $user = User::where('id', $request->userId)->first();
        $userTypeFetch = $user->type;

        if($userTypeFetch != "instructor") {
            return ResponseHelper::error('Profile not found');
        }

        $userType = $user->instructor;

        $userType->title = $request->title;
        $userType->professional_headline = $request->headline;
        $userType->category = $request->category;
        $userType->bio = $request->bio;
        $userType->linkedin_url = $request->linkedin;
        $userType->youtube_url = $request->youtube;
        $userType->twitter_url = $request->twitter;
        $userType->website = $request->website;
        $userType->save();

        return ResponseHelper::success('Details Updated Successfully', ['user' => $user, $userTypeFetch => $userType]);
    }

    public function submitExperiences(Request $request) {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:users,id',
            'experiences' => 'required|array',
            'experiences.*.title' => 'required|string|max:255',
            'experiences.*.organization' => 'required|string|max:255',
            'experiences.*.description' => 'nullable|string',
            'experiences.*.start_date' => 'required|date',
            'experiences.*.end_date' => 'nullable|date',
            'experiences.*.currently_working' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $user = User::where('id', $request->userId)->first();
        $userTypeFetch = $user->type;

        if($userTypeFetch != "instructor") {
            return ResponseHelper::error('Profile not found');
        }

        $userType = $user->instructor;

        foreach ($request->experiences as $exp) {
            Experience::create([
                'instructor_id' => $userType->id,
                'title' => $exp['title'],
                'organization' => $exp['organization'],
                'description' => $exp['description'],
                'start_date' => $exp['start_date'],
                'end_date' => $exp['currently_working'] ? null : $exp['end_date'],
                'is_current' => $exp['currently_working'],
            ]);
        }

        $user->profile_progress = '4';
        $user->save();

        return ResponseHelper::success('Details Updated Successfully', ['user' => $user, $userTypeFetch => $userType]);
    }

    public function addExperiences(Request $request) {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:users,id',
            'experiences' => 'required|array',
            'experiences.*.title' => 'required|string|max:255',
            'experiences.*.organization' => 'required|string|max:255',
            'experiences.*.description' => 'nullable|string',
            'experiences.*.start_date' => 'required|date',
            'experiences.*.end_date' => 'nullable|date',
            'experiences.*.currently_working' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $user = User::where('id', $request->userId)->first();
        $userTypeFetch = $user->type;

        if($userTypeFetch != "instructor") {
            return ResponseHelper::error('Profile not found');
        }

        $userType = $user->instructor;

        foreach ($request->experiences as $exp) {
            Experience::create([
                'instructor_id' => $userType->id,
                'title' => $exp['title'],
                'organization' => $exp['organization'],
                'description' => $exp['description'],
                'start_date' => $exp['start_date'],
                'end_date' => $exp['currently_working'] ? null : $exp['end_date'],
                'is_current' => $exp['currently_working'],
            ]);
        }

        return ResponseHelper::success('Details Updated Successfully', ['user' => $user, $userTypeFetch => $userType]);
    }

    public function editExperience(Request $request) {
        Log::info($request);
        $data = $request->input('experience');
        $validator = Validator::make($data, [
            'id' => 'required|exists:experiences,id',
            'title' => 'required|string|max:255',
            'organization' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'currently_working' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $experience = Experience::where('id', $request->experience['id'])->first();

        $update = $experience->update([
            'title' => $request->experience['title'],
            'organization' => $request->experience['organization'],
            'description' => $request->experience['description'],
            'start_date' => $request->experience['start_date'],
            'end_date' => $request->experience['currently_working'] ? null : $request->experience['end_date'],
            'is_current' => $request->experience['currently_working'],
        ]);

        return ResponseHelper::success('Details Updated Successfully', ['experience' => $experience]);
    }

    public function deleteExperience(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:experiences,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $experience = Experience::where('id', $request->id)->first();
        $experience->delete();

        return ResponseHelper::success('Experience deleted successfully');
    }

    public function instructorExperiences(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:instructors,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $experiences = Experience::where('instructor_id', $request->id)->orderBy('id', 'desc')->get();

        return ResponseHelper::success('Details fetched successfully', ['experiences' => $experiences]);
    }

}
