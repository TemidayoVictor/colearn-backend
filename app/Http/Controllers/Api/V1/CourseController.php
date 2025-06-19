<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Validator;

use App\Models\User;
use App\Models\Course;

class CourseController extends Controller
{
    //
    public function uploadCourse(Request $request) {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:users,id',
            'title' => 'required|string',
            'description' => 'required|string',
            'who_can_enroll' => 'required|string',
            'price' => 'required|integer',
            'is_free' => 'boolean',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $user = ModelHelper::findOrFailWithCustomResponse(User::class, $request->userId, 'User not found', 'userId');
        $userTypeFetch = $user->type;

        if($userTypeFetch != "instructor") {
            return ResponseHelper::error('Profile not found');
        }

        $userType = $user->instructor;

        $course = Course::create([
            'instructor_id' => $userType->id,
            'title' => $request ->title,
            'description' => $request->description,
            'who_can_enroll' => $reques->who_can_enroll,
            'price' => $request->is_free ? null : $request->price,
            'is_free' => $request->is_free,
        ]);

        return ResponseHelper::success('Course created successfully');
    }
}
