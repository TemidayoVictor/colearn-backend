<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\GeneralSetting;
use App\Models\Enrollment;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\Consultant;
use App\Models\VideoProgress;
use App\Models\Review;
use App\Models\Category;
use App\Models\Blog;
use App\Models\InstructorReview;

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

    public function instructorDashboard(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $userId = $request->id;
        $instructor = Instructor::with('courses')->where('user_id', $userId)->first();
        $instructorId = $instructor->id;

        $courseIds = $instructor->courses->pluck('id')->toArray();

        // 1. Total Sales Amount
        $totalSalesAmount = DB::table('cart')
            ->whereIn('course_id', $courseIds)
            ->where('status', 'checked_out')
            ->sum('purchase_price');

        // 2. Total Course Uploads
        $totalCourses = count($courseIds);

        // 3. Total Enrollments
        $totalEnrollments = DB::table('enrollments')
            ->whereIn('course_id', $courseIds)
            ->count();

        // 4. Total Courses Completed
        $totalCompleted = DB::table('enrollments')
            ->whereIn('course_id', $courseIds)
            ->whereNotNull('completed_at')
            ->count();

        // 5. Wallet Balance
        $wallet = Wallet::where('user_id', $userId)->first();

        // 6. Monthly Earnings
        $monthlyEarnings = DB::table('transactions')
        ->select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(amount) as total')
        )
        ->where('user_id', $userId)
        ->where('type', 'credit')
        ->groupBy(DB::raw('MONTH(created_at)'))
        ->pluck('total', 'month');

        // Initialize all months to 0
        $earnings = [];

        for ($i = 1; $i <= 12; $i++) {
            $earnings[] = $monthlyEarnings[$i] ?? 0;
        }

        // 7. Total Revenue
        $totalRevenue = DB::table('transactions')
        ->where('user_id', $userId)
        ->where('type', 'credit')
        ->sum('amount');

        $courses = Course::withCount([
            'enrollments as total_enrollments',
            'enrollments as total_completions' => function ($query) {
                $query->whereNotNull('completed_at');
            },
            'reviews as review_count',
        ])
        ->withSum('cart as total_revenue', 'purchase_price') // from carts
        ->withAvg('reviews as average_rating', 'rating') // from reviews
        ->where('instructor_id', $instructorId)
        ->get();

        $reviews = Review::whereIn('course_id', $courseIds)->get();
        // Total number of reviews
        $totalReviews = $reviews->count();

        // Overall rating (average)
        $overallRating = $totalReviews > 0 ? round($reviews->avg('rating'), 1) : 0;

        return ResponseHelper::success("Data fetched successfully", [
            'total_sales_amount' => $totalSalesAmount,
            'total_courses_uploaded' => $totalCourses,
            'total_enrollments' => $totalEnrollments,
            'total_courses_completed' => $totalCompleted,
            'wallet' => $wallet,
            'earnings' => $earnings,
            'total_revenue' => $totalRevenue,
            'courses' => $courses,
            'total_reviews' => $totalReviews,
            'total_average_rating' => $overallRating,
        ]);
    }

    public function webData() {
        $categories = Category::all();
        $courses = Course::with('instructor.user', 'reviews.user', 'enrollments', 'resources')->where('is_published', true)->inRandomOrder()->get();
        $instructors = Instructor::whereNotNull('title')->with('user', 'courses.modules.videos')->inRandomOrder()->get(); // instructors who have completed their profile
        $consultants = Consultant::with('instructor.user')->inRandomOrder()->get();
        $blogs = Blog::all();
        return ResponseHelper::success("Data fetched successfully", [
            'categories' => $categories,
            'courses' => $courses,
            'instructors' => $instructors,
            'consultants' => $consultants,
            'blogs' => $blogs
        ]);
    }

    public function instructorDataWeb(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $userId = $request->id;
        $instructor = Instructor::with('user','courses', 'consultant')->where('user_id', $userId)->first();
        $instructorId = $instructor->id;

        $courseIds = $instructor->courses->pluck('id')->toArray();

        // 1. Total Sales Amount
        $totalSalesAmount = DB::table('cart')
            ->whereIn('course_id', $courseIds)
            ->where('status', 'checked_out')
            ->sum('purchase_price');

        // 2. Total Course Uploads
        $totalCourses = count($courseIds);

        // 3. Total Enrollments
        $totalEnrollments = DB::table('enrollments')
            ->whereIn('course_id', $courseIds)
            ->count();

        // 4. Total Courses Completed
        $totalCompleted = DB::table('enrollments')
            ->whereIn('course_id', $courseIds)
            ->whereNotNull('completed_at')
            ->count();

        // 5. Wallet Balance
        $wallet = Wallet::where('user_id', $userId)->first();

        // 6. Monthly Earnings
        $monthlyEarnings = DB::table('transactions')
        ->select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(amount) as total')
        )
        ->where('user_id', $userId)
        ->where('type', 'credit')
        ->groupBy(DB::raw('MONTH(created_at)'))
        ->pluck('total', 'month');

        // Initialize all months to 0
        $earnings = [];

        for ($i = 1; $i <= 12; $i++) {
            $earnings[] = $monthlyEarnings[$i] ?? 0;
        }

        // 7. Total Revenue
        $totalRevenue = DB::table('transactions')
        ->where('user_id', $userId)
        ->where('type', 'credit')
        ->sum('amount');

        $courses = Course::withCount([
            'enrollments as total_enrollments',
            'enrollments as total_completions' => function ($query) {
                $query->whereNotNull('completed_at');
            },
            'reviews as review_count',
        ])
        ->withSum('cart as total_revenue', 'purchase_price') // from carts
        ->withAvg('reviews as average_rating', 'rating') // from reviews
        ->where('instructor_id', $instructorId)
        ->get();

        $reviews = Review::whereIn('course_id', $courseIds)->with('user')->get();
        // Total number of reviews
        $totalReviews = $reviews->count();

        // Overall rating (average)
        $overallRating = $totalReviews > 0 ? round($reviews->avg('rating'), 1) : 0;

        $courses = Course::with('instructor.user')->where('instructor_id', $instructorId)->get();

        return ResponseHelper::success("Data fetched successfully", [
            'total_sales_amount' => $totalSalesAmount,
            'total_courses_uploaded' => $totalCourses,
            'total_enrollments' => $totalEnrollments,
            'total_courses_completed' => $totalCompleted,
            'wallet' => $wallet,
            'earnings' => $earnings,
            'total_revenue' => $totalRevenue,
            'courses' => $courses,
            'total_reviews' => $totalReviews,
            'total_average_rating' => $overallRating,
            'instructor' => $instructor,
            'reviews' => $reviews,
            'courses' => $courses,
        ]);
    }

    public function review(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'instructor_id' => 'required|exists:instructors,id',
            'title' => 'required',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        // Update if already reviewed
        $review = InstructorReview::updateOrCreate(
            ['user_id' => $request->user_id, 'instructor_id' => $request->instructor_id],
            ['rating' => $request->rating, 'review' => $request->review, 'title' => $request->title]
        );

        return ResponseHelper::success('Review submitted successfully.', ['review' => $review]);
    }
}
