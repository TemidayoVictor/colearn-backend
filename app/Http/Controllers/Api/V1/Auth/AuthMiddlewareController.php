<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;

use App\Models\Instructor;
use App\Models\Student;

class AuthMiddlewareController extends Controller
{
    //
    public function authenticateUser(Request $request) {
        $user = $request->user();

        $response = [
            'user' => $user,
        ];

        if ($user->type === 'student') {
            $response['student'] = $user->student;
        } elseif ($user->type === 'instructor') {
            $response['instructor'] = $user->instructor;
        }

        return response()->json($response);
    }

    public function authenticateUserInstructor(Request $request) {
        $user = $request->user();

        $response = [
            'user' => $user,
        ];

        if($user) {
            if ($user->type === 'instructor') {
                $instructor = Instructor::where('user_id', $user->id)->with('schools', 'certifications', 'consultant.slots')->first();
                $response['instructor'] = $instructor;
            }
        }

        return response()->json($response);
    }

    public function authenticateUserStudent(Request $request) {
        $user = $request->user();

        $response = [
            'user' => $user,
        ];

        if($user) {
            if ($user->type === 'student') {
                $student = Student::where('user_id', $user->id)->first();
                $response['student'] = $student;
            }
        }

        return response()->json($response);
    }
}
