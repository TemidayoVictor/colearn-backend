<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use App\Helpers\ModelHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

use App\Models\User;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Instructor;
use App\Models\Category;

class CourseController extends Controller
{
    //
    public function uploadCourse(Request $request) {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:users,id',
            'title' => 'required|string',
            'description' => 'required|string',
            'who_can_enroll' => 'required|string',
            'price' => 'nullable|integer',
            'is_free' => 'string',
            'categories' => 'required|array',
            'categories.*' => 'string',
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

        $checkFree = false;
        if($request->is_free != 'false') {
            $checkFree = true;
        }

        $course = Course::create([
            'instructor_id' => $userType->id,
            'title' => $request ->title,
            'description' => $request->description,
            'who_can_enroll' => $request->who_can_enroll,
            'price' => $checkFree ? null : $request->price,
            'is_free' => $checkFree,
        ]);

        $categories = $request->categories;
        foreach($categories as $category) {
            $categoryFetch = Category::where('name', $category)->firstOrFail();
            CourseCategory::create([
                'course_id' => $course->id,
                'category_id' => $categoryFetch->id,
            ]);
        }

        return ResponseHelper::success('Course created successfully', ['course' => $course]);
    }

    public function allCourses(Request $request) {
        $validator = Validator::make($request->all(), [
            'instructorId' => 'required|exists:instructors,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $instructor = ModelHelper::findOrFailWithCustomResponse(Instructor::class, $request->instructorId, 'Instructor not found', 'instructorId');

        $courses = Course::where('instructor_id', $request->instructorId)->get();
        return ResponseHelper::success('Courses fetched successfully', ['courses' => $courses]);
    }

    public function getCourse(Request $request) {
        $validator = Validator::make($request->all(), [
            'courseId' => 'required|string',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $course = ModelHelper::findOrFailWithCustomResponse(Course::class, $request->courseId, 'Course not found', 'courseId');
        return ResponseHelper::success('Course fetched successfully', ['course' => $course]);
    }
}
