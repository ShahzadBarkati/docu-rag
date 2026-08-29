<?php

namespace App\Http\Controllers\Api;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class ListDocumentsController
{
    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::success($request->user()->documents()->latest()->get());
    }
}
