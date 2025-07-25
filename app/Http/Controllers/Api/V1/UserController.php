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
}
