<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Stevebauman\Location\Facades\Location;
use App\Helpers\TimeZoneHelper;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\GeneralSetting;
use App\Models\Instructor;
use App\Models\Consultant;
use App\Models\Course;
use App\Models\Cart;
use App\Models\Enrollment;
use App\Models\Review;

class AdminController extends Controller
{
    //
    public function adminDashboard() {
        $totalStudents = User::where('type', 'student')->count();

        // Active students — adjust criteria as needed
        $activeStudents = User::where('type', 'student')
        ->whereHas('enrollments')
        ->count();

        $newStudents = User::where('type', 'student')
        ->where('created_at', '>=', now()->subDays(7))
        ->count();

        // Prevent division by zero
        $percentageStudentActive = $totalStudents > 0
        ? round(($activeStudents / $totalStudents) * 100, 2)
        : 0;

        $totalInstructors = Instructor::get()->count();
        $activeInstructors = Instructor::whereHas('courses')->count();
        $newInstructors = Instructor::where('created_at', '>=', now()->subDays(7))->count();

        $totalConsultants = Consultant::get()->count();
        $activeConsultants = Consultant::whereHas('bookings')->count();
        $newConsultants = Consultant::where('created_at', '>=', now()->subDays(7))->count();

        // Prevent division by zero
        $percentageInstructorsActive = $totalInstructors > 0
        ? round(($activeInstructors / $totalInstructors) * 100, 2)
        : 0;

        $totalSalesAmount = DB::table('cart')
        ->where('status', 'checked_out')
        ->sum('purchase_price');

        $monthlySalesAmount = DB::table('cart')
        ->where('status', 'checked_out')
        ->whereMonth('created_at', Carbon::now()->month)
        ->whereYear('created_at', Carbon::now()->year)
        ->sum('purchase_price');

        $totalConsultationAmount =Transaction::where('description', 'like', 'Consultation Session between%')
        ->where('user_type', 'Admin')
        ->sum('amount');

        $monthlyEarnings = DB::table('transactions')
        ->select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(amount) as total')
        )
        ->where('user_type', 'Admin')
        ->where('type', 'credit')
        ->groupBy(DB::raw('MONTH(created_at)'))
        ->pluck('total', 'month');

        // Initialize all months to 0
        $earnings = [];

        for ($i = 1; $i <= 12; $i++) {
            $earnings[] = $monthlyEarnings[$i] ?? 0;
        }

        $totalRevenue = DB::table('transactions')
        ->where('user_type', 'Admin')
        ->where('type', 'credit')
        ->sum('amount');

        $usersByCountry = DB::table('users')
        ->select('country_iso3', DB::raw('count(*) as user_count'))
        ->whereNotNull('country_iso3')
        ->groupBy('country_iso3')
        ->orderByDesc('user_count')
        ->get();

        $topCourses = Course::withCount('enrollments')
        ->orderByDesc('enrollments_count')
        ->take(6)
        ->get();

        return ResponseHelper::success("Data fetched successfully", [
            'percentage_student_active' => $percentageStudentActive,
            'percentage_instructors_active' => $percentageInstructorsActive,
            'total_sales_amount' => $totalSalesAmount,
            'monthly_sales_amount' => $monthlySalesAmount,
            'total_consultation_amount' => $totalConsultationAmount,
            'total_students' => $totalStudents,
            'active_students' => $activeStudents,
            'total_instructors' => $totalInstructors,
            'active_instructors' => $activeInstructors,
            'total_consultants' => $totalConsultants,
            'active_consultants' => $activeConsultants,
            'new_students' => $newStudents,
            'new_instructors' => $newInstructors,
            'new_consultants' => $newConsultants,
            'earnings' => $earnings,
            'total_revenue' => $totalRevenue,
            'users_by_country' => $usersByCountry,
            'top_courses' => $topCourses,
        ]);

    }

    public function adminCourses() {
        $totalSalesAmount = Cart::where('coupon_status', 'completed')->sum('purchase_price');
        $totalCourses = Course::count();
        $totalCoursesPublished = Course::where('is_published', true)->count();
        $totalCompletedCourses = Enrollment::whereNotNull('completed_at')->count();
        $totalEnrollments = Enrollment::count();
        $totalReviews = Review::count();

        $courses = Course::withCount([
            'enrollments as total_enrollments',
            'enrollments as total_completions' => function ($query) {
                $query->whereNotNull('completed_at');
            },
            'reviews as review_count',
        ])
        ->withSum('cart as total_revenue', 'purchase_price') // from carts
        ->withAvg('reviews as average_rating', 'rating') // from reviews
        ->with('instructor.user')
        ->get();

        $topInstructors = Instructor::with('courses', 'user')
        ->withSum('totalSales as total_sales', 'purchase_price')
        ->orderByDesc('total_sales')
        ->take(6)
        ->get();

        $topCourses = Course::withCount('enrollments')
        ->orderByDesc('enrollments_count')
        ->take(6)
        ->get();

        return ResponseHelper::success("Data fetched successfully", [
            'total_sales_amount' => $totalSalesAmount,
            'total_courses_uploaded' => $totalCourses,
            'total_courses_published' => $totalCoursesPublished,
            'total_courses_completed' => $totalCompletedCourses,
            'total_reviews' => $totalReviews,
            'top_instructors' => $topInstructors,
            'top_courses' => $topCourses,
            'total_enrollments' => $totalEnrollments,
            'courses' => $courses,
        ]);
    }

    public function addAdmin(Request $request) {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'role' => 'required',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $ip = '102.89.39.111'; // or use a test IP like '102.89.39.111 $request->ip()'
        $position = Location::get($ip);

        $timezone = null;
        if ($position && $position->countryCode) {
            $timezone = TimezoneHelper::mapCountryToTimezone($position->countryCode);
        }

        $emailVerificationCode = random_int(100000, 999999);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => 'admin',
            'email_verification_code' => $emailVerificationCode,
            'timezone' => $timezone,
            'status' => 'Active',
            'profile_progress' => 'completed',
        ]);

        return ResponseHelper::success("Account created successfully");

    }

    public function creditWallet(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
            'amount' => 'required|integer',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $user = User::where('id', $request->id)->first();
        $userType = $user->type;

        // debit admiin wallet
        $adminWallet = Wallet::where('type', 'Admin')->first();
        $adminBalance = $adminWallet->balance;
        $newAdminBalance = $adminBalance - $request->amount;

        if($newAdminBalance < 0) {
            return ResponseHelper::error("Admin balance is less than $".$request->amount);
        }

        $adminWallet->balance = $newAdminBalance;
        $adminWallet->save();

        // credit user wallet
        $wallet = Wallet::firstOrCreate(
            [
                'user_id' => $request->id,
                'type' => $userType,
            ]
        );
        $initialBalance = $wallet->balance;
        $newBalance = $initialBalance + $request->amount;
        $wallet->balance = $newBalance;
        $wallet->save();

        // create transaction
        $message = "Credit from Colearn";
        $admMessage = "Funds sent to ".$user->first_name." ".$user->last_name;
        $reference = Str::uuid()->toString();
        $adminReference = Str::uuid()->toString();

        // create transaction for both user and admin

        $transaction = Transaction::create([
            'user_id' => $request->id,
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'amount' => $request->amount,
            'reference' => $reference,
            'description' => $message,
            'user_type' => $userType,
        ]);

        $adminTransaction = Transaction::create([
            'user_id' => $adminWallet->user_id,
            'wallet_id' => $adminWallet->id,
            'type' => 'debit',
            'amount' => $request->amount,
            'reference' => 'adm-'.$adminReference,
            'description' => $admMessage,
            'user_type' => 'Admin',
        ]);

        return ResponseHelper::success("Wallet credited successfully");

    }

    public function debitWallet(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
            'amount' => 'required|integer',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $user = User::where('id', $request->id)->first();
        $userType = $user->type;

        // debit user wallet

        $wallet = Wallet::firstOrCreate(
            [
                'user_id' => $request->id,
                'type' => $userType,
            ]
        );

        $initialBalance = $wallet->balance;
        $newBalance = $initialBalance - $request->amount;

        if($newBalance < 0) {
            return ResponseHelper::error("User balance is less than $".$request->amount);
        }

        $wallet->balance = $newBalance;
        $wallet->save();

        // credit admiin wallet
        $adminWallet = Wallet::where('type', 'Admin')->first();
        $adminBalance = $adminWallet->balance;
        $adminWallet->balance = $adminBalance + $request->amount;
        $adminWallet->save();

        // create transaction
        $message = "Debit from Colearn";
        $admMessage = "Funds collected from ".$user->first_name." ".$user->last_name;
        $reference = Str::uuid()->toString();
        $adminReference = Str::uuid()->toString();

        // create transaction for both user and admin

        $transaction = Transaction::create([
            'user_id' => $request->id,
            'wallet_id' => $wallet->id,
            'type' => 'debit',
            'amount' => $request->amount,
            'reference' => $reference,
            'description' => $message,
            'user_type' => $userType,
        ]);

        $adminTransaction = Transaction::create([
            'user_id' => $adminWallet->user_id,
            'wallet_id' => $adminWallet->id,
            'type' => 'credit',
            'amount' => $request->amount,
            'reference' => 'adm-'.$adminReference,
            'description' => $admMessage,
            'user_type' => 'Admin',
        ]);

        return ResponseHelper::success("Wallet debited successfully");
    }

    public function withdrawFunds(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
            'amount' => 'required|integer',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $settings = GeneralSetting::first();
        if($request->amount < $settings->minimum_withdrawal) {
            return ResponseHelper::error("Minimum withdrawal amount is $".$settings->minimum_withdrawal);
        }

        $wallet = Wallet::where('user_id', $request->id)->first();
        if($wallet->balance < $request->amount) {
            return ResponseHelper::error("Insufficient funds in wallet");
        }

        $user = User::where('id', $request->id)->first();
        $userType = $user->type;

        $message = "Funds Withdrawal";
        $reference = Str::uuid()->toString();

        $transaction = Transaction::create([
            'user_id' => $request->id,
            'wallet_id' => $wallet->id,
            'type' => 'debit',
            'amount' => $request->amount,
            'reference' => $reference,
            'description' => $message,
            'user_type' => $userType,
            'status' => 'pending',
        ]);

        return ResponseHelper::success("Request sent successfully");

    }

    public function adminCredit(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
            'amount' => 'required|integer',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $user = User::where('id', $request->id)->first();
        $userType = $user->type;

        // credit admiin wallet spendable
        $adminWallet = Wallet::where('type', 'Admin')->first();
        $adminBalance = $adminWallet->balance;
        $adminSpendable = $adminWallet->spendable;
        $newBalance = $adminBalance - $request->amount;

        if($newBalance < 0) {
            return ResponseHelper::error('Insufficient Amount');
        }

        $adminWallet->balance = $newBalance;
        $adminWallet->spendable = $adminSpendable + $request->amount;
        $adminWallet->save();

        // create transaction
        $message = "Admin credit by ".$user->first_name." ".$user->last_name;
        $reference = Str::uuid()->toString();

        // create transaction
        $adminTransaction = Transaction::create([
            'user_id' => $adminWallet->user_id,
            'wallet_id' => $adminWallet->id,
            'type' => 'credit',
            'amount' => $request->amount,
            'reference' => 'adm-pr-'.$reference,
            'description' => $message,
            'user_type' => 'Admin_Profit',
        ]);

        return ResponseHelper::success("Wallet credited successfully");

    }

    public function adminDebit(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
            'amount' => 'required|integer',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $user = User::where('id', $request->id)->first();
        $userType = $user->type;

        // debit admiin wallet spendable
        $adminWallet = Wallet::where('type', 'Admin')->first();
        $adminSpendable = $adminWallet->spendable;
        $adminWallet->spendable = $adminSpendable - $request->amount;
        $adminWallet->save();

        // create transaction
        $message = "Admin debit by ".$user->first_name." ".$user->last_name;
        $reference = Str::uuid()->toString();

        $adminTransaction = Transaction::create([
            'user_id' => $adminWallet->user_id,
            'wallet_id' => $adminWallet->id,
            'type' => 'debit',
            'amount' => $request->amount,
            'reference' => 'adm-pr-'.$reference,
            'description' => $message,
            'user_type' => 'Admin_Debit',
        ]);

        return ResponseHelper::success("Wallet debited successfully");

    }

    public function allTransactions(Request $request) {
        $transactions = Transaction::with('user')->get()->groupBy(function($transaction) {
            return Carbon::parse($transaction->created_at)->format('F Y');
        });

        $sortedGrouped = $transactions->sortByDesc(function ($_, $key) {
            return Carbon::createFromFormat('F Y', $key);
        });

        $adminWallet = Wallet::where('type', 'Admin')->first();
        $settings = GeneralSetting::first();
        $withdrawals = Transaction::where('status', 'pending')->where('user_type', '!=', 'Admin')->with('user', 'wallet')->get();

        return ResponseHelper::success("Data fetched successfully", ['transactions' => $sortedGrouped, 'adminWallet' => $adminWallet, 'settings' => $settings, 'withdrawals' => $withdrawals]);
    }

    public function adminTransactions(Request $request) {
        $transactions = Transaction::where('user_type', 'Admin')->with('user')->get()->groupBy(function($transaction) {
            return Carbon::parse($transaction->created_at)->format('F Y');
        });

        $sortedGrouped = $transactions->sortByDesc(function ($_, $key) {
            return Carbon::createFromFormat('F Y', $key);
        });

        $adminWallet = Wallet::where('type', 'Admin')->first();

        return ResponseHelper::success("Data fetched successfully", ['transactions' => $sortedGrouped, 'adminWallet' => $adminWallet]);
    }

    public function adminCreditTransactions(Request $request) {
        $transactions = Transaction::where('user_type', 'Admin')
            ->where('type', 'credit')
            ->with('user')->get()
            ->groupBy(function($transaction) {
                return Carbon::parse($transaction->created_at)->format('F Y');
        });

        $sortedGrouped = $transactions->sortByDesc(function ($_, $key) {
            return Carbon::createFromFormat('F Y', $key);
        });

        $adminWallet = Wallet::where('type', 'Admin')->first();

        return ResponseHelper::success("Data fetched successfully", ['transactions' => $sortedGrouped, 'adminWallet' => $adminWallet]);
    }

    public function adminDebitTransactions(Request $request) {
        $transactions = Transaction::where('user_type', 'Admin')
            ->where('type', 'debit')
            ->with('user')->get()
            ->groupBy(function($transaction) {
                return Carbon::parse($transaction->created_at)->format('F Y');
        });

        $sortedGrouped = $transactions->sortByDesc(function ($_, $key) {
            return Carbon::createFromFormat('F Y', $key);
        });

        $adminWallet = Wallet::where('type', 'Admin')->first();

        return ResponseHelper::success("Data fetched successfully", ['transactions' => $sortedGrouped, 'adminWallet' => $adminWallet]);
    }

    public function allUsers() {
        $users = User::where('type', '!=', 'Admin')->get();
        return ResponseHelper::success("Data fetched successfully", ['users' => $users]);
    }

    public function getUserDetails(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $user = User::where('id', $request->id)->first();
        $userType = $user->type;

        if($userType == 'student') {
            $user = User::where('id', $request->id)->with('student', 'wallet')->first();
        }

        else if($userType == 'instructor') {
            $user = User::where('id', $request->id)->with('instructor', 'wallet')->first();
        }

        return ResponseHelper::success("Data fetched successfully", ['user' => $user]);
    }

    public function updateGeneralSettings(Request $request) {
        $validator = Validator::make($request->all(), [
            'course_percentage' => 'required|integer',
            'consultation_perentage' => 'required|integer',
            'minimum_withdrawal' => 'required|integer',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $settings = GeneralSetting::first();

        $settings->course_percentage = $request->course_percentage;
        $settings->consultation_perentage = $request->consultation_perentage;
        $settings->minimum_withdrawal = $request->minimum_withdrawal;
        $settings->save();

        return ResponseHelper::success("General settings updated successfully", ['settings' => $settings]);
    }

    public function approveWithdrawal(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:transactions,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $transaction = Transaction::where('id', $request->id)->first();
        if($transaction->status == 'approved') {
            return ResponseHelper::error("Withdrawal request already approved");
        }
        if($transaction->status == 'declined') {
            return ResponseHelper::error("Withdrawal request already declined");
        }

        $transaction->status = 'approved';
        $transaction->processed_at = Carbon::now();
        $transaction->save();

        $user = User::where('id', $transaction->user_id)->first();
        $userType = $user->type;

        $wallet = Wallet::where('user_id', $transaction->user_id)->first();
        $newBalance = $wallet->balance - $transaction->amount;
        if($newBalance < 0) {
            return ResponseHelper::error("Insufficient funds in wallet");
        }

        $wallet->balance = $newBalance;
        $wallet->save();

        return ResponseHelper::success("Withdrawal Aproved Successfully");
    }

    public function rejectWithdrawal(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:transactions,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $transaction = Transaction::where('id', $request->id)->first();
        if($transaction->status == 'rejected') {
            return ResponseHelper::error("Withdrawal request already declined");
        }

        $transaction->status = 'declined';
        $transaction->processed_at = Carbon::now();
        $transaction->save();

        return ResponseHelper::success("Withdrawal Declined Successfully");
    }
}
