<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SignUpController extends Controller
{
    public function test() {
        return response()->json([
            ['data' => 'Brand A'],
        ]);
    }
}
