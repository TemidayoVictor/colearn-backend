<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use App\Helpers\ModelHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use App\Models\User;
use App\Models\Course;
use App\Models\CoursesSection;
use App\Models\CourseCategory;
use App\Models\CoursesVideo;
use App\Models\CoursesResource;
use App\Models\Instructor;
use App\Models\Category;

class CourseController extends Controller
{
    //Course functions
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
            'course_picture' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $user = User::where('id', $request->userId)->first();
        $userTypeFetch = $user->type;

        if($userTypeFetch != "instructor") {
            return ResponseHelper::error('Instructor profile not found');
        }

        $userType = $user->instructor;

        if ($request->hasFile('course_picture')) {
            $path = $request->file('course_picture')->store('uploads/course_thumbnails', 'public');
        }

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
            'thumbnail' => $path,
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

    public function editCourse(Request $request) {
        $validator = Validator::make($request->all(), [
            'courseId' => 'required|exists:courses,id',
            'title' => 'required|string',
            'description' => 'required|string',
            'who_can_enroll' => 'required|string',
            'price' => 'nullable|integer',
            'is_free' => 'string',
            'categories' => 'required|array',
            'categories.*' => 'string',
            'course_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        Log::info($request);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $checkFree = false;
        if($request->is_free != 'false') {
            $checkFree = true;
        }

        $course = Course::where('id', $request->courseId)->first();

        $path = $course->thumbnail;
        if ($request->hasFile('course_picture')) {
            // store new image
            $path = $request->file('course_picture')->store('uploads/course_thumbnails', 'public');

            // delete previous video
            if ($course->thumbnail != null) {
                if (Storage::disk('public')->exists($course->thumbnail)) {
                    Storage::disk('public')->delete($course->thumbnail);
                }
            }
        }

        $course->title = $request->title;
        $course->description = $request->description;
        $course->who_can_enroll = $request->who_can_enroll;
        $course->price = $request->price;
        $course->is_free = $checkFree;
        $course->thumbnail = $path;
        $course->save();

        // fetch and delete all previous categories
        $previousCategories = CourseCategory::where('course_id', $request->courseId)->get();
        if($previousCategories) {
            foreach($previousCategories as $category) {
                $category->delete();
            }
        }

        $categories = $request->categories;
        foreach($categories as $category) {
            $categoryFetch = Category::where('name', $category)->firstOrFail();
            CourseCategory::create([
                'course_id' => $course->id,
                'category_id' => $categoryFetch->id,
            ]);
        }

        return ResponseHelper::success('Course updated successfully', ['course' => $course]);
    }

    public function getCourse(Request $request) {
        $validator = Validator::make($request->all(), [
            'courseId' => 'required|exists:courses,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $courseUse = Course::with([
            'modules' => function ($query) {
                $query->orderBy('order');
            },
            'modules.videos' => function ($query) {
                $query->orderBy('order');
            },
            'resources'
        ])->where('id', $request->courseId)->first();

        return ResponseHelper::success('Data fetched successfully', ['course' => $courseUse]);
    }

    public function getCourseEdit(Request $request) {
        $validator = Validator::make($request->all(), [
            'courseId' => 'required|exists:courses,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $courseUse = Course::with('categories')->findOrFail($request->courseId);
        return ResponseHelper::success('Data fetched successfully', ['course' => $courseUse]);
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

    // Module Functions
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

    public function editModule(Request $request) {

        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
            'order' => 'required',
            'courseId' => 'required|exists:courses,id',
            'moduleId' => 'required|exists:course_sections,id',
        ]);


        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $module = CoursesSection::where('id', $request->moduleId)->first();

        $requestOrder = $request->order;
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
                $highestOrder = $modulesCount;
                $lowestOrder = '1';

                return ResponseHelper::error('Invalid Position. Highest postion is '.$highestOrder.' and lowest position is '.$lowestOrder.'.');
            }
        }

        else {
            $newOrder = $request->order;
        }

        // update module
        $module->title = $request->title;
        $module->description = $request->description;
        $module->order = $newOrder;
        $module->save();

        return ResponseHelper::success('Module Updated successfully', ['module' => $module]);
    }

    public function getModule(Request $request) {
        $validator = Validator::make($request->all(), [
            'moduleId' => 'required|string|exists:course_sections,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }
        $moduleUse = CoursesSection::with([
            'videos' => function ($query) {
                $query->orderBy('order');
            },
            'resources'
        ])->where('id', $request->moduleId)->first();
        return ResponseHelper::success('Module fetched successfully', ['module' => $moduleUse]);
    }

    // Video Functions
    public function uploadVideo(Request $request) {
        $validator = Validator::make($request->all(), [
            'moduleId' => 'required|exists:course_sections,id',
            'title' => 'required|string',
            'video' => 'required|file|mimes:mp4,mov,avi,webm,mkv|max:512000', // 500MB max
            'duration' => 'required',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        // get the number of all the videos in that module to get order
        $videosCount = CoursesVideo::where('course_section_id', $request->moduleId)->count();
        $order = $videosCount + 1;

        // get module to know number of videos under module and number of videos under course and increment.
        $module = CoursesSection::where('id', $request->moduleId)->first();
        $moduleVideos = $module->videos;
        $courseId = $module->course_id;
        $course = Course::where('id', $courseId)->first();
        $courseVideos = $course->videos;

        // store video
        $path = null;
        if ($request->hasFile('video')) {
            $path = $request->file('video')->store('uploads/lesson_videos', 'public');
        }

        // store details in table
        $video = CoursesVideo::create([
            'course_section_id' => $request->moduleId,
            'title' => $request->title,
            'video_url' => $path,
            'duration' => $request->duration,
            'order' => $order,
        ]);

        // update the module and course videos count
        $module->videos = $moduleVideos + 1;
        $module->save();

        $course->videos = $courseVideos + 1;
        $course->save();

        return ResponseHelper::success('Video Uploaded successfully', ['video' => $video]);

    }

    public function editVideo(Request $request) {

        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'video' => 'nullable|file|mimes:mp4,mov,avi,webm,mkv|max:512000', // 500MB max
            'duration' => 'required',
            'order' => 'required',
            'moduleId' => 'required|exists:course_sections,id',
            'videoId' => 'required|exists:course_videos,id',
        ]);


        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $video = CoursesVideo::where('id', $request->videoId)->first();

        $requestOrder = $request->order;
        $previousOrder = $video->order;

        $path = $video->video_url;
        if ($request->hasFile('video')) {
            // store new video
            $path = $request->file('video')->store('uploads/lesson_videos', 'public');

            // delete previous video
            if (Storage::disk('public')->exists($video->video_url)) {
                Storage::disk('public')->delete($video->video_url);
            }
        }

        // check for change in order
        if($previousOrder != $requestOrder) {
            // change deteted
            // check module that had that order, and swap
            $videoWithNewOrder = CoursesVideo::where('course_section_id', $request->moduleId)->where('order', $request->order)->first();

            if($videoWithNewOrder) {
                $videoWithNewOrder->order = $previousOrder;
                $videoWithNewOrder->save();

                $newOrder = $request->order;
            }

            else {
                // no video was found with that order
                // return error message
                $videosCount = CoursesVideo::where('course_section_id', $request->moduleId)->count();
                $highestOrder = $videosCount;
                $lowestOrder = '1';

                return ResponseHelper::error('Invalid Position. Highest position is '.$highestOrder.' and lowest position is '.$lowestOrder.'.');
            }
        }

        else {
            $newOrder = $request->order;
        }

        // update module
        $video->title = $request->title;
        $video->video_url = $path;
        $video->duration = $request->duration;
        $video->order = $newOrder;
        $video->save();

        return ResponseHelper::success('Video Updated successfully', ['video' => $video]);
    }

    // Resource Functions
    public function uploadResource(Request $request) {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'type' => 'required',
            'category' => 'required',
            'courseId' => 'required|exists:courses,id',
            'videoId' => 'nullable|exists:course_videos,id',
            'moduleId' => 'nullable|exists:course_sections,id',
            'document' => 'nullable|file|mimes:docs,pdf,txt,pptx,xlsx,csv,zip,rar|max:5200', // max 5MB
            'url' => 'nullable',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $type = $request->type;
        $document = $request->document;
        $url = $request->url;
        $path = null;

        if($type == 'document' && $document == null) {
            return ResponseHelper::error('No document found');
        }

        elseif($type == 'link' && $url == null) {
            return ResponseHelper::error('No URL found');
        }

        if($type == 'document' && $document != null) {
            // store document
            if ($request->hasFile('document')) {
                $path = $request->file('document')->store('uploads/resources', 'public');
            }
        }

        $resource = CoursesResource::create([
            'course_id' => $request->courseId,
            'course_section_id' => $request->moduleId,
            'course_video_id' => $request->videoId,
            'title' => $request->title,
            'type' => $request->type,
            'category' => $request->category,
            'file_path' => $path,
            'content' => null,
            'external_url' => $url,
        ]);

        return ResponseHelper::success('Resource added successfully');
    }

    public function editResource(Request $request) {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'type' => 'required',
            'category' => 'required',
            'videoId' => 'nullable|exists:course_videos,id',
            'moduleId' => 'nullable|exists:course_sections,id',
            'document' => 'nullable|file|mimes:docs,pdf,txt,pptx,xlsx,csv,zip,rar|max:5200', // max 5MB
            'url' => 'nullable',
            'resourceId' => 'required|exists:course_resources,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $resource = CoursesResource::where('id', $request->resourceId)->first();
        $path = $resource->file_path;
        $previousType = $resource->type;

        $type = $request->type;
        $document = $request->document;
        $url = $request->url;

        if($type == 'document' && $document == null && $path == null  ) {
            return ResponseHelper::error('No document found');
        }

        elseif($type == 'link' && $url == null) {
            return ResponseHelper::error('No URL found');
        }

        if($type == 'document' && $document != null) {
            // store document
            if ($request->hasFile('document')) {
                $path = $request->file('document')->store('uploads/resources', 'public');
            }
        }

        if(($previousType == 'document' && $type !== 'document') || ($previousType == 'document' && $document != null)) {
            // delete previous document
            if (Storage::disk('public')->exists($resource->file_path)) {
                Storage::disk('public')->delete($resource->file_path);
            }
        }

        // update database
        $resource->course_section_id = $request->moduleId;
        $resource->course_video_id = $request->videoId;
        $resource->title = $request->title;
        $resource->type = $request->type;
        $resource->category = $request->category;
        $resource->file_path = $path;
        $resource->external_url = $url;
        $resource->save();

        return ResponseHelper::success('Resource updated successfully');
    }
}
