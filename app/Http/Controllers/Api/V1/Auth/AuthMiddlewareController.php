<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;

class AuthMiddlewareController extends Controller
{
    //
    public function authenticateUser(Request $request) {
        $user = $request->user();

        $response = [
            'user' => $user,
        ];

        if ($user->type === 'student') {
            $response['student'] = $user->student; // Assuming relationship exists
        } elseif ($user->type === 'instructor') {
            $response['instructor'] = $user->instructor; // If you plan to handle instructors
        }

        return response()->json($response);
    }
}
