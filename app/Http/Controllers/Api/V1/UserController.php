<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Carbon;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\GeneralSetting;
use App\Models\Enrollment;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\VideoProgress;

class UserController extends Controller
{
    //

    public function getUserTransactions(Request $request) {

        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $transactions = Transaction::with('user')->where('user_id', $request->id)->get()->groupBy(function($transaction) {
            return Carbon::parse($transaction->created_at)->format('F Y');
        });

        $sortedGrouped = $transactions->sortByDesc(function ($_, $key) {
            return Carbon::createFromFormat('F Y', $key);
        });

        $wallet = Wallet::where('user_id', $request->id)->first();

        return ResponseHelper::success("Data fetched successfully", ['transactions' => $sortedGrouped, 'wallet' => $wallet]);
    }

    public function studentDashboard(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $userId = $request->id;

        $enrollments = Enrollment::where('user_id', $userId)
        ->with('course.instructor.user')
        ->get()
        ->map(function ($enrollment) use ($userId) {
            $course = $enrollment->course;

            // Count completed videos for this course by this user
            $completedCount = VideoProgress::where('course_id', $course->id)
                ->where('user_id', $userId)
                ->whereNotNull('completed_at')
                ->count();

            $totalVideos = (int) $course->videos_count;

            $progress = $totalVideos > 0
                ? round(($completedCount / $totalVideos) * 100, 2)
                : 0;

            return [
                'course' => $course,
                'progress' => $progress,
                'enrollment' => $enrollment,
            ];
        });

        $popularCourses = Course::with('instructor.user')->where('is_published', true)->inRandomOrder()->take(4)->get();
        $instructors = Instructor::whereHas('user', function ($query) {
            $query->where('profile_progress', 'completed');
        })
        ->with('user')
        ->inRandomOrder()
        ->take(4)
        ->get();
        $totalProgress = $enrollments->avg('progress');

        return ResponseHelper::success("Data fetched successfully", [
            'enrollments' => $enrollments,
            'popularCourses' => $popularCourses,
            'instructors' => $instructors,
            'totalProgress' => round($totalProgress, 0),
        ]);
    }

    public function userProfile(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $userId = $request->id;
        $user = User::with('student')->where('id', $userId)->first();
        $completedCourses = Enrollment::where('user_id', $userId)->whereNotNull('completed_at')->count();

        return ResponseHelper::success("Data fetched successfully", ['user' => $user, 'completedCourses' => $completedCourses]);

    }
}
