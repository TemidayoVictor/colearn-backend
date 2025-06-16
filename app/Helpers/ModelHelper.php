<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Helpers\ResponseHelper;

class ModelHelper
{
    /**
     * Attempt to find a model by ID or return custom error response.
     */
    public static function findOrFailWithCustomResponse(
        string $modelClass,
        $id,
        string $errorMessage = 'Resource not found.',
        string $key = 'id',
        int $statusCode = 404
    ): Model|\Illuminate\Http\JsonResponse {
        try {
            return $modelClass::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::error(
                $errorMessage,
                [$key => [$errorMessage]],
                $statusCode
            );
        }
    }
}
