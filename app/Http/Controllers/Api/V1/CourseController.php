<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;

class CourseController extends Controller
{
    //
    public function uploadCourse(Request $request) {
        return ResponseHelper::success('Course called');
    }
}
