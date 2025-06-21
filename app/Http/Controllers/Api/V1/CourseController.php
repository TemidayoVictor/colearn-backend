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
use App\Models\CoursesSection;
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

    public function addModules(Request $request) {

        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
            'courseId' => 'required|exists:courses,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $course = ModelHelper::findOrFailWithCustomResponse(Course::class, $request->courseId, 'Course not found', 'courseId');

        // get the number of all the modules to get order
        $modulesCount = CoursesSection::where('course_id', $request->courseId)->count();
        $order = $modulesCount + 1;

        // create module
        $module = CoursesSection::create([
            'course_id' => $request->courseId,
            'title' => $request->title,
            'description' => $request->description,
            'order' => $order,
        ]);

        return ResponseHelper::success('Module Added successfully', ['module' => $module]);
    }

    public function editModules(Request $request) {

        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
            'order' => 'required',
            'courseId' => 'required|exists:courses,id',
            'moduleId' => 'required',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $module = ModelHelper::findOrFailWithCustomResponse(CoursesSection::class, $request->moduleId, 'Module not found', 'moduleId');
        $previousOrder = $module->order;

        // check for change in order
        if($previousOrder != $requestOrder) {
            // change deteted
            // check module that had that order, and swap
            $moduleWithNewOrder = CoursesSection::where('course_id', $request->courseId)->where('order', $request->order)->first();

            if($moduleWithNewOrder) {
                $moduleWithNewOrder->order = $previousOrder;
                $moduleWithNewOrder->save();

                $newOrder = $request->order;
            }

            else {
                // no module was found with that order
                // make the module the last order
                // get the number of all the modules to get order
                $modulesCount = CoursesSection::where('course_id', $request->courseId)->count();
                $newOrder = $modulesCount + 1;
            }
        }

        // update module
        $module->title = $request->title;
        $module->description = $request->description;
        $module->order = $newOrder;
        $module->save();

        return ResponseHelper::success('Module Updated successfully', ['module' => $module]);
    }

    public function allCourseModules(Request $request) {
        $validator = Validator::make($request->all(), [
            'courseId' => 'required|exists:courses,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $course = ModelHelper::findOrFailWithCustomResponse(Course::class, $request->courseId, 'Course not found', 'courseId');

        $modules = CoursesSection::where('course_id', $request->courseId)->get();
        return ResponseHelper::success('Courses fetched successfully', ['course' => $course, 'modules' => $modules]);
    }

    public function getModule(Request $request) {
        $validator = Validator::make($request->all(), [
            'moduleId' => 'required|string',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $module = ModelHelper::findOrFailWithCustomResponse(CoursesSection::class, $request->moduleId, 'Module not found', 'moduleId');
        return ResponseHelper::success('Module fetched successfully', ['module' => $module]);
    }
}
