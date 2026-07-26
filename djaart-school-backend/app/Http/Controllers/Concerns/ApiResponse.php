<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success(mixed $data = null, string $message = '', int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'message' => $message,
            'errors' => null,
        ], $status);
    }

    protected function error(string $message, mixed $errors = null, int $status = 422): JsonResponse
    {
        return response()->json([
            'data' => null,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
