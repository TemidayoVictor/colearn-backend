<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use App\Helpers\ModelHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Stripe\Stripe;
use Stripe\Checkout\Session;

use App\Models\User;
use App\Models\Course;
use App\Models\CoursesSection;
use App\Models\CourseCategory;
use App\Models\CoursesVideo;
use App\Models\CoursesResource;
use App\Models\Instructor;
use App\Models\Category;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Enrollment;
use App\Models\VideoProgress;
use App\Models\ModuleProgress;
use App\Models\Review;
use App\Models\GeneralSetting;
use App\Models\Transaction;
use App\Models\Wallet;

class CourseController extends Controller
{
    //Course functions
    public function allCourses(Request $request) {
        $courses = Course::with('instructor.user')->where('is_published', true)->inRandomOrder()->take(18)->get();
        return ResponseHelper::success('Courses fetched successfully', ['courses' => $courses]);
    }

    public function allInstructorsCourses(Request $request) {
        $validator = Validator::make($request->all(), [
            'instructorId' => 'required|exists:instructors,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $courses = Course::where('instructor_id', $request->instructorId)->with('enrollments', 'reviews')->get();
        return ResponseHelper::success('Courses fetched successfully', ['courses' => $courses]);
    }

    public function uploadCourse(Request $request) {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:users,id',
            'title' => 'required|string',
            'description' => 'required|string',
            'who_can_enroll' => 'required|string',
            'price' => 'nullable',
            'is_free' => 'string',
            'categories' => 'required|array',
            'categories.*' => 'string',
            'course_picture' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'total_duration' => 'required',
            'level' => 'required',
            'summary' => 'required|string|max:500',
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
            'total_duration' => $request->total_duration,
            'level' => $request->level,
            'summary' => $request->summary,
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
            'price' => 'nullable',
            'is_free' => 'string',
            'categories' => 'required|array',
            'categories.*' => 'string',
            'course_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'total_duration' => 'required',
            'level' => 'required',
            'summary' => 'required|string|max:500',
        ]);

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
        $course->total_duration = $request->total_duration;
        $course->level = $request->level;
        $course->summary = $request->summary;
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
        Log::info($request);
        $validator = Validator::make($request->all(), [
            'courseId' => 'required|exists:courses,id',
            'userId'   => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $userId = $request->userId;

        $courseUse = Course::with([
            'modules' => function ($query) {
                $query->orderBy('order');
            },
            'modules.videos' => function ($query) {
                $query->orderBy('order');
            },
            'modules.progresses' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            },
            'modules.videos.progresses' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            },
            'resources'
        ])->where('id', $request->courseId)->first();

        return ResponseHelper::success('Data fetched successfully', ['course' => $courseUse]);
    }

    public function getCourseStudent(Request $request) {
        $validator = Validator::make($request->all(), [
            'courseId' => 'required|exists:courses,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $course = Course::with('reviews.user', 'enrollments')->where('id', $request->courseId)->first();

        return ResponseHelper::success('Data fetched successfully', ['course' => $course]);
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

    public function deleteCourse(Request $request) {
        $validator = Validator::make($request->all(), [
            'courseId' => 'required|exists:courses,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $course = Course::where('id', $request->courseId)->first();

        $modules = CoursesSection::where('course_id', $request->courseId)->get();

        foreach($modules as $module) {
            // fetch and delete all videos under module
            $videos = CoursesVideo::where('course_section_id', $request->moduleId)->get();
            foreach($videos as $video) {
                $path = $video->video_url;

                // if there is an attached file, delete it
                if($path != null) {
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                }

                $video->delete();
            }

            // delete module
            $module->delete();
        }

        // delete course
        $course->delete();
        return ResponseHelper::success('Course deleted successfully');
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

    public function deleteModule(Request $request) {
        $validator = Validator::make($request->all(), [
            'moduleId' => 'required|string|exists:course_sections,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $module = CoursesSection::where('id', $request->moduleId)->first();

        // fetch and delete all videos under module
        $videos = CoursesVideo::where('course_section_id', $request->moduleId)->get();
        foreach($videos as $video) {
            $path = $video->video_url;

            // if there is an attached file, delete it
            if($path != null) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            $video->delete();
        }

        // delete module
        $module->delete();
        return ResponseHelper::success('Module deleted successfully');
    }

    // Video Functions
    public function uploadVideo(Request $request) {
        $validator = Validator::make($request->all(), [
            'moduleId' => 'required|exists:course_sections,id',
            'title' => 'required|string',
            'video' => 'nullable|file|mimes:mp4,mov,avi,webm,mkv|max:512000', // 500MB max
            'body' => 'nullable',
            'type' => 'required',
            'duration' => 'required',
            'set_intro' => 'string'
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
        $moduleVideos = $module->videos_count;
        $courseId = $module->course_id;
        $course = Course::where('id', $courseId)->first();
        $courseVideos = $course->videos_count;

        if($request->type == 'video' && !$request->video) {
            return ResponseHelper::error('Please add a video');
        }

        elseif($request->type == 'text' && !$request->body) {
            return ResponseHelper::error('Please add a body to the content');
        }

        // store video
        $path = 'null';
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
            'overall_order' => $courseVideos + 1,
            'type' => $request->type,
            'body' => $request->body,
        ]);

        // update the module and course videos count
        $module->videos_count = $moduleVideos + 1;
        $module->save();

        $course->videos_count = $courseVideos + 1;

        // check if user marked video as introductory or this is the first video uploaded by the user for the course
        if($request->set_intro != 'false' || !$courseVideos || $courseVideos == 0) {
            $course->intro_video_url = $path;
        }
        $course->save();

        // update the status of the module progress to incomplete
        $allModuleProgresses = ModuleProgress::where('course_section_id', $request->moduleId)->get();
        if($allModuleProgresses) {
            foreach($allModuleProgresses as $progress) {
                $progress->update([
                    'completed_at' => null,
                ]);
            }
        }

        // update the status of all the course enrollment progress
        $allCourseEnrollments = Enrollment::where('course_id', $courseId)->get();
        if($allCourseEnrollments) {
            foreach($allCourseEnrollments as $progress) {
                $progress->update([
                    'completed_at' => null,
                ]);
            }
        }

        return ResponseHelper::success('Lecture Uploaded successfully', ['video' => $video]);

    }

    public function editVideo(Request $request) {

        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'video' => 'nullable|file|mimes:mp4,mov,avi,webm,mkv|max:512000', // 500MB max
            'duration' => 'required',
            'order' => 'required',
            'moduleId' => 'required|exists:course_sections,id',
            'videoId' => 'required|exists:course_videos,id',
            'body' => 'nullable',
            'type' => 'required',
            'set_intro' => 'string',
        ]);


        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $video = CoursesVideo::where('id', $request->videoId)->first();

        if($request->type == 'video' && (!$request->video && $video->video_url == 'null')) {
            return ResponseHelper::error('Please add a video');
        }

        elseif($request->type == 'text' && !$request->body) {
            return ResponseHelper::error('Please add a body to the content');
        }

        $requestOrder = $request->order;
        $previousOrder = $video->order;
        $previousOverallOrder = $video->overall_order;

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
                $videoWithNewOrderOverallOrder = $videoWithNewOrder->overall_order;

                $videoWithNewOrder->order = $previousOrder;
                $videoWithNewOrder->overall_order = $previousOverallOrder;
                $videoWithNewOrder->save();

                $newOrder = $request->order;
                $newOverallOrder = $videoWithNewOrderOverallOrder;
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
            $newOverallOrder = $previousOverallOrder;
        }

        // update module
        $video->title = $request->title;
        $video->video_url = $path;
        $video->duration = $request->duration;
        $video->order = $newOrder;
        $video->overall_order = $newOverallOrder;
        $video->type = $request->type;
        $video->body = $request->body;
        $video->save();

        $module = CoursesSection::where('id', $request->moduleId)->first();
        $courseId = $module->course_id;
        $course = Course::where('id', $courseId)->first();

        if($request->set_intro != 'false') {
            $course->intro_video_url = $path;
            $course->save();
        }

        return ResponseHelper::success('Lecture Updated successfully', ['video' => $video]);
    }

    public function deleteVideo(Request $request) {
        $validator = Validator::make($request->all(), [
            'videoId' => 'required|exists:course_videos,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $video = CoursesVideo::where('id', $request->videoId)->first();
        $path = $video->video_url;

        // if there is an attached file, delete it
        if($path != null) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        // get module to know number of videos under module and number of videos under course and increment.
        $module = CoursesSection::where('id', $video->course_section_id)->first();
        $moduleVideos = $module->videos;
        $courseId = $module->course_id;
        $course = Course::where('id', $courseId)->first();
        $courseVideos = $course->videos;

        // update the module and course videos count
        if($module->videos > 0) {
            $module->videos = $moduleVideos - 1;
            $module->save();
        }

        if($course->videos > 0) {
            $course->videos = $courseVideos - 1;
            $course->save();
        }

        $video->delete();
        return ResponseHelper::success('Resource deleted successfully');

    }

    public function watchVideo(Request $request) {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:users,id',
            'videoId' => 'required|exists:course_videos,id',
            'courseId' => 'required|exists:courses,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $video = CoursesVideo::where('id', $request->videoId)->first();
        $moduleId = $video->course_section_id;
        $overallOrder = $video->overall_order;

        $checkVideoProgress = VideoProgress::where('user_id', $request->userId)
        ->where('course_id', $request->courseId)
        ->where('course_video_id', $request->videoId)
        ->first();

        if(!$checkVideoProgress) {
            $progress = VideoProgress::create([
                'user_id' => $request->userId,
                'course_id' => $request->courseId,
                'course_video_id' => $request->videoId,
            ]);
        }

        $checkModuleProgress = ModuleProgress::where('user_id', $request->userId)
        ->where('course_section_id', $moduleId)
        ->first();

        if(!$checkModuleProgress) {
            $progress = ModuleProgress::create([
                'user_id' => $request->userId,
                'course_section_id' => $moduleId,
            ]);
        }

        $courseId = (int) ($request->courseId ?? 0);

        $nextVideo = CoursesVideo::where('overall_order', '>', $overallOrder)
        ->whereHas('module', function ($query) use ($courseId) {
            $query->where('course_id', '=', $courseId);
        })
        ->orderBy('overall_order')
        ->first();

        $prevVideo = CoursesVideo::where('overall_order', '<', $overallOrder)
        ->whereHas('module', function ($query) use ($courseId) {
            $query->where('course_id', '=', $courseId);
        })
        ->orderByDesc('overall_order')
        ->first();

        return ResponseHelper::success('Progress updated successfully', ['video' => $video, 'nextVideo' => $nextVideo, 'prevVideo' => $prevVideo]);

    }

    public function markVideoAsComplete(Request $request) {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:users,id',
            'videoId' => 'required|exists:course_videos,id',
            'courseId' => 'required|exists:courses,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $video = CoursesVideo::with('module')->findOrFail($request->videoId);
        $moduleId = $video->course_section_id;

        $checkVideo = VideoProgress::where('user_id', $request->userId)
        ->where('course_id', $request->courseId)
        ->where('course_video_id', $request->videoId)
        ->first();

        if($checkVideo) {
            $update = $checkVideo->update([
                'completed_at' => now(),
                'watched_percentage' => 100,
            ]);
        }

        $courseId = $request->courseId;

        // check if the whole module is completed
        $allVideoIds = CoursesVideo::where('course_section_id', $moduleId)->pluck('id');

        $completedCount = VideoProgress::where('user_id', $request->userId)
        ->whereIn('course_video_id', $allVideoIds)
        ->whereNotNull('completed_at')
        ->count();

        if ($completedCount === $allVideoIds->count()) {
            // module is completed
            $checkModule =  ModuleProgress::where('user_id', $request->userId)
            ->where('course_section_id', $moduleId)
            ->first();

            if($checkModule) {
                $update = $checkModule->update([
                    'completed_at' => now(),
                ]);
            }
        }

        // check if the whole course is completed
        $allModuleIds = CoursesSection::where('course_id', $courseId)->pluck('id');

        $completedModuleCount = ModuleProgress::where('user_id', $request->userId)
        ->whereIn('course_section_id', $allModuleIds)
        ->whereNotNull('completed_at')
        ->count();

        if ($completedModuleCount === $allModuleIds->count()) {
            // course is completed
            $checkCourse =  Enrollment::where('user_id', $request->userId)
            ->where('course_id', $courseId)
            ->first();

            if($checkCourse) {
                $update = $checkCourse->update([
                    'completed_at' => now(),
                ]);
            }
        }

        return ResponseHelper::success('Video completed successfully');

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

    public function deleteResource(Request $request) {
        $validator = Validator::make($request->all(), [
            'resourceId' => 'required|exists:course_resources,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $resource = CoursesResource::where('id', $request->resourceId)->first();
        $path = $resource->file_path;

        // if there is an attached file, delete it
        if($path != null) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $resource->delete();
        return ResponseHelper::success('Resource deleted successfully');

    }

    public function publishCourse(Request $request) {
        $validator = Validator::make($request->all(), [
            'courseId' => 'required|exists:courses,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $course = Course::where('id', $request->courseId)->first();
        $videos = $course->videos;

        if($videos == null || $videos == 0) {
            return ResponseHelper::error('Please upload at least one video in order to publish course');
        }

        $course->is_published = true;
        $course->save();
        return ResponseHelper::success('Course published successfully');
    }

    public function addToCart(Request $request) {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:users,id',
            'courseId' => 'required|exists:courses,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $checkCart = Cart::where('user_id', $request->userId)->where('course_id', $request->courseId)->first();

        if($checkCart) {
            if($checkCart->status == 'active') {
                return ResponseHelper::success('Course already added to cart');
            }

            elseif($checkCart->status == 'checked_out') {
                return ResponseHelper::success('You have already purchased this course');
            }
        }

        $cart = Cart::create([
            'user_id' => $request->userId,
            'course_id' => $request->courseId,
            'status' => 'active',
        ]);

        return ResponseHelper::success('Course added to cart successfully', ['cart' => $cart]);

    }

    public function getCart(Request $request) {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $cart = Cart::where('user_id', $request->userId)->where('status', 'active')->with('user', 'course.instructor.user', 'coupon')->orderBy('id', 'desc')->get();

        return ResponseHelper::success('Cart fetched successfully', ['cart' => $cart]);
    }

    public function removeFromCart(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:cart,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $cart = Cart::where('id', $request->id)->where('status', 'active')->first();
        $cart->delete();

        return ResponseHelper::success('Course removed successfully');
    }

    public function getCoupons(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:instructors,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $coupons = Coupon::where('instructor_id', $request->id)->where('status', 'Valid')->orderBy('id', 'desc')->get();
        return ResponseHelper::success('Coupons fetched successfully', ['coupons' => $coupons]);
    }

    public function addCoupon(Request $request) {
        $validator = Validator::make($request->all(), [
            'code' => 'required|exists:coupons,code',
            'id' => 'required|exists:cart,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $coupon = Coupon::where('code', $request->code)->first();

        if(!$coupon || !$coupon->isValid()) {
            return ResponseHelper::error('Invalid / Expired coupon code');
        }

        $couponCartCount = Cart::where('coupon_id', $coupon->id)->count();

        if($couponCartCount >= $coupon->usage_limit) {
            return ResponseHelper::error('Coupon code Limit reached');
        }

        $couponInstructor = $coupon->instructor_id;

        $cart = Cart::where('id', $request->id)->with('course')->first();
        $coursePrice = $cart->course->price;

        $courseInstructor = $cart->course->instructor_id;

        if($couponInstructor != $courseInstructor) {
            return ResponseHelper::error('This coupon is not valid for this course.');
        }

        $update = $cart->update([
            'coupon_id' => $coupon->id,
            'coupon_status' => 'pending',
        ]);

        return ResponseHelper::success('Coupon added successfully');
    }

    public function createCoupon(Request $request) {
        $validator = Validator::make($request->all(), [
            'code' => 'nullable|string|unique:coupons,code',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0.01',
            'usage_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:today',
            'instructorId' => 'required',
            'amount' => 'nullable',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        // If user did not add code, generate code
        if(!$request->code) {
            $code = strtoupper(Str::random(6));
        }

        else {
            $code = $request->code;
        }

        if(!$request->usage_limit) {
            $limit = 1;
        }

        else {
            $limit = $request->usage_limit;
        }

        $coupon = Coupon::create([
            'code' => $code,
            'type' => $request->type,
            'value' => $request->value,
            'instructor_id' => $request->instructorId,
            'usage_limit' => $limit,
            'expires_at' => $request->expires_at,
            'status' => 'Valid',
        ]);

        if($request->amount && $request->amount > 1) {
            // request to create more than one coupon. We'll create amount - 1 coupons, because one has already been created
            $amount = $request->amount - 1;
            for ($i = 0; $i < $amount; $i++) {
                $code = strtoupper(Str::random(6));

                $coupon = Coupon::create([
                    'code' => $code,
                    'type' => $request->type,
                    'value' => $request->value,
                    'instructor_id' => $request->instructorId,
                    'usage_limit' => $limit,
                    'expires_at' => $request->expires_at,
                    'status' => 'Valid',
                ]);
            }
        }

        return ResponseHelper::success('Coupon created successfully');

    }

    public function deleteCoupon(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|string|exists:coupons,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $coupon = Coupon::where('id', $request->id)->first();

        if($coupon) {
            $coupon->status = 'Deleted';
            $coupon->expires_at = Carbon::yesterday();
            $coupon->save();
        }

        return ResponseHelper::success('Coupon deleted successfully');
    }

    public function checkoutCalculate(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
            'cart' => 'required|array',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $total = 0;
        $allCart = $request->cart;

        foreach ($allCart as $data) {
            $cartId = $data['id'];

            // Fetch cart with course and coupon
            $cart = Cart::where('id', $cartId)->with('course', 'coupon')->first();

            if (!$cart || !$cart->course || $cart->user_id != $request->id) {
                continue; // skip invalid or incomplete items
            }

            $price = $cart->course->price;

            // Handle coupon logic
            if ($cart->coupon) {
                if ($cart->coupon->isValid()) {
                    $coupon = $cart->coupon;

                    if ($coupon->type === 'percent') {
                        $discount = ($price * $coupon->value) / 100;
                        $price -= $discount;
                    } elseif ($coupon->type === 'fixed') {
                        $price -= $coupon->value;
                    }

                    if ($price < 0) {
                        $price = 0;
                    }

                } else {
                    // Coupon is invalid — remove it from the cart
                    $cart->coupon_id = null;
                    $cart->coupon_status = null;
                    $cart->save();
                }
            }

            $total += $price;
        }

        return ResponseHelper::success('Verification successful', ['total' => round($total, 2)]);
    }

    public function stripeCheckout(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
            'cart' => 'required|array',
            'total' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $user = User::where('id', $request->id)->first();
        if (!$user) {
            return ResponseHelper::error('User not found', [], 404);
        }

        $userEmail = $user->email;
        $allCart = $request->cart;

        Stripe::setApiKey(config('services.stripe.secret'));

        $lineItems = [];

        foreach ($allCart as $data) {
            $cartId = $data['id'];

            // Fetch the full cart with course and coupon details
            $cart = Cart::where('id', $cartId)
                ->with('course.instructor.user', 'coupon')
                ->first();

            if (!$cart || !$cart->course || $cart->user_id != $request->id) {
                continue; // skip invalid or incomplete cart items
            }

            // Apply coupon if available
            $price = $cart->course->price;
            if ($cart->coupon) {
                if ($cart->coupon->type === 'percentage') {
                    $price = $price - ($price * ($cart->coupon->value / 100));
                } else {
                    $price = $price - $cart->coupon->value;
                }

                $price = max($price, 0); // prevent negative price
            }

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $cart->course->title,
                    ],
                    'unit_amount' => round($price * 100), // Stripe expects amount in cents
                ],
                'quantity' => $cart->quantity ?? 1, // default to 1 if quantity not set
            ];
        }

        $session = Session::create([
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => route('enroll.student', [], true) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => 'http://localhost:3000/students/cart',
            'customer_email' => $userEmail,
            'metadata' => [
                'user_id' => $user->id,
                'cart_ids' => implode(',', collect($allCart)->pluck('id')->toArray()),// Save cart in metadata
            ]
        ]);

        if($session) {
            return ResponseHelper::success('Checkout success', ['url' => $session->url]);
        }

        else {
            return ResponseHelper::error('An error occured');
        }
    }

    public function enroll(Request $request) {
        // $validator = Validator::make($request->all(), [
        //     'id' => 'required|exists:users,id',
        //     'cart' => 'required|array',
        // ]);

        // if ($validator->fails()) {
        //     $firstError = $validator->errors()->first();
        //     return ResponseHelper::error($firstError, $validator->errors(), 422);
        // }

        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return ResponseHelper::error('No session ID provided');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = \Stripe\Checkout\Session::retrieve($sessionId);
        $allCart = explode(',', $session->metadata->cart_ids);
        $userId = $session->metadata->user_id;

        $total = 0;
        $general = GeneralSetting::first();
        $percentage = $general->course_percentage;

        foreach ($allCart as $cartId) {
            $cart = Cart::where('id', $cartId)->with('course.instructor.user', 'coupon')->first();

            if (!$cart || !$cart->course || $cart->user_id != $userId) {
                continue; // skip invalid or incomplete items
            }

            $price = $cart->course->price;

            if ($cart->coupon) {
                $coupon = $cart->coupon;
                $coupon->increment('used_count');

                if ($coupon->type === 'percent') {
                    $discount = ($price * $coupon->value) / 100;
                    $price -= $discount;
                } elseif ($coupon->type === 'fixed') {
                    $price -= $coupon->value;
                }

                if ($price < 0) {
                    $price = 0;
                }
            }

            $cart->coupon_status = 'completed';
            $cart->status = 'checked_out';
            $cart->purchase_price = $price;
            $cart->save();

            $enrollment = Enrollment::create([
                'user_id' => $userId,
                'course_id' => $cart->course->id,
            ]);

            // update the instructors wallet
            $instructorUserId = $cart->course->instructor->user->id;
            $instructorEarning = ($percentage * $price) / 100;
            $adminEarning = $price - $instructorEarning;

            $userWallet = Wallet::firstOrCreate(
                [
                    'user_id' => $instructorUserId,
                    'type' => 'Instructor',
                ]
            );

            $instructorBalance = $userWallet->balance;
            $userWallet->balance = $instructorBalance + $instructorEarning;
            $userWallet->save();

            // update admin wallet with the balance
            $adminWallet = Wallet::where('type', 'Admin')->first();
            $adminSpendable = $adminWallet->spendable;
            $adminWallet->spendable = $adminSpendable + $adminEarning;
            $adminWallet->save();

            // create a transaction for it
            $message = "Purchase of ".$cart->course->title." course";
            $reference = Str::uuid()->toString();
            $adminReference = Str::uuid()->toString();

            // create transaction for both user and admin

            $transaction = Transaction::create([
                'user_id' => $instructorUserId,
                'wallet_id' => $userWallet->id,
                'type' => 'credit',
                'amount' => $instructorEarning,
                'reference' => $reference,
                'description' => $message,
                'user_type' => 'Instructor',
            ]);

            $adminTransaction = Transaction::create([
                'user_id' => $adminWallet->user_id,
                'wallet_id' => $adminWallet->id,
                'type' => 'credit',
                'amount' => $adminEarning,
                'reference' => 'adm-pr-'.$adminReference,
                'description' => $message,
                'user_type' => 'Admin_Profit',
            ]);

        }

        return redirect()->to('http://localhost:3000/students/courses?message=Course Enrollment Successful');

    }

    public function cancelPayment(Request $request) {
        return ResponseHelper::success('Payment cancelled successfully');
    }

    public function enrolledCourses(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $userId = $request->id;

        $courses = Enrollment::where('user_id', $userId)
        ->with(['course.instructor.user', 'course.resources'])
        ->get();

        $reviews = Review::where('user_id', $userId)->get()->keyBy('course_id');

        // Attach each review manually
        $courses->each(function ($enrollment) use ($reviews) {
            $enrollment->review = $reviews->get($enrollment->course_id);
        });

        return ResponseHelper::success('Courses fetched successfully', ['courses' => $courses]);

    }

    public function review(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'title' => 'required',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        // Optional: Check if user is enrolled
        $enrolled = Enrollment::where('user_id', $request->user_id)
        ->where('course_id', $request->course_id)
        ->exists();

        if (!$enrolled) {
            return ResponseHelper::error('Only enrolled users can submit a review');
        }

        // Update if already reviewed
        $review = Review::updateOrCreate(
            ['user_id' => $request->user_id, 'course_id' => $request->course_id],
            ['rating' => $request->rating, 'review' => $request->review, 'title' => $request->title]
        );

        return ResponseHelper::success('Review submitted successfully.', ['review' => $review]);
    }

    public function search(Request $request) {
        $validator = Validator::make($request->all(), [
            'keyword' => 'required',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $keyword = $request->input('keyword');

        $courses = Course::where(function ($query) use ($keyword) {
            $query->where('title', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
        })
        ->where('is_published', true)
        ->with('instructor.user', 'enrollments', 'reviews')
        ->get();

        return ResponseHelper::success('Data fetched successfully.', ['courses' => $courses]);
    }


    public function robustSearch(Request $request) {
        // Might still need tweaking. Not yet tested
        $query = Course::query()->with(['instructor', 'categories'])
            ->where('is_published', true); // only published

        // Keyword search (title, summary, description)
        if ($request->filled('q')) {
            $keyword = $request->input('q');
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%$keyword%")
                ->orWhere('summary', 'like', "%$keyword%")
                ->orWhere('description', 'like', "%$keyword%");
            });
        }

        // Filter by level
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        // Filter by is_free (true/false)
        if ($request->has('is_free')) {
            $query->where('is_free', filter_var($request->is_free, FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by category
        if ($request->filled('category')) {
            $category = $request->category;
            $query->whereHas('categories', function ($q) use ($category) {
                $q->where('name', $category);
            });
        }

        // Filter by instructor name
        if ($request->filled('instructor')) {
            $name = $request->instructor;
            $query->whereHas('instructor.user', function ($q) use ($name) {
                $q->where('name', 'like', "%$name%");
            });
        }

        // Sorting
        switch ($request->input('sort')) {
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'most_enrolled':
                $query->withCount('enrollments')->orderBy('enrollments_count', 'desc');
                break;
            case 'free_first':
                $query->orderBy('is_free', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        // Paginate
        $courses = $query->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $courses,
        ]);
    }

}
