<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function success(mixed $data = [], string $message = 'OK', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'message' => $message,
        ], $statusCode);
    }

    public static function error(string $message = 'Failed! Error occurred', int $statusCode = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }
}
