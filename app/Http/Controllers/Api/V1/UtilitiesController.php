<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;

use App\Models\Country;

class UtilitiesController extends Controller
{
    public function countries() {
        $countries = Country::all();
        return ResponseHelper::success('Countries Fetched', ['countries' => $countries]);
    }
}
