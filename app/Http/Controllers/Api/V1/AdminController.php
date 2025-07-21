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

use App\Models\User;
use App\Models\Transaction;
use App\Models\Wallet;

class AdminController extends Controller
{
    //
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
        $wallet = Wallet::where('user_id', $request->id)->first();
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
        $wallet = Wallet::where('user_id', $request->id)->first();
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
        $adminWallet->balance = $adminBalance - $request->amount;
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
        $adminBalance = $adminWallet->balance;

        return ResponseHelper::success("Data fetched successfully", ['transactions' => $sortedGrouped, 'adminBalance' => $adminBalance]);
    }

    public function adminTransactions(Request $request) {
        $transactions = Transaction::where('user_type', 'Admin')->with('user')->get()->groupBy(function($transaction) {
            return Carbon::parse($transaction->created_at)->format('F Y');
        });

        $sortedGrouped = $transactions->sortByDesc(function ($_, $key) {
            return Carbon::createFromFormat('F Y', $key);
        });

        $adminWallet = Wallet::where('type', 'Admin')->first();
        $adminBalance = $adminWallet->balance;

        return ResponseHelper::success("Data fetched successfully", ['transactions' => $sortedGrouped, 'adminBalance' => $adminBalance]);
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
        $adminBalance = $adminWallet->balance;

        return ResponseHelper::success("Data fetched successfully", ['transactions' => $sortedGrouped, 'adminBalance' => $adminBalance]);
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
        $adminBalance = $adminWallet->balance;

        return ResponseHelper::success("Data fetched successfully", ['transactions' => $sortedGrouped, 'adminBalance' => $adminBalance]);
    }
}
