<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Storage;

use App\Models\Country;

class UtilitiesController extends Controller
{
    public function countries() {
        $countries = Country::all();
        return ResponseHelper::success('Countries Fetched', ['countries' => $countries]);
    }

    public function downloadResource($filename, $title = null) {
        $path = 'uploads/resources/' . $filename;

        if (!Storage::exists($path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        // Get the extension from the original filename
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        // Set a fallback name if title is not provided
        $safeTitle = $title ? $title : 'document';

        // Optionally sanitize title (remove special characters/spaces)
        $safeTitle = preg_replace('/[^A-Za-z0-9\-_]/', '_', $safeTitle);

        $customFilename = $safeTitle . '.' . $extension;

        return Storage::download($path, $customFilename);
    }
}
