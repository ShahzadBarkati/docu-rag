<?php

namespace App\Http\Controllers\Api;

use App\Services\Auth\Contracts\AuthenticationContract;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class LogoutController
{
    public function __construct(private readonly AuthenticationContract $auth) {}

    public function __invoke(Request $request): JsonResponse
    {
        $this->auth->logout($request->user());

        return ApiResponse::success(message: 'Logged out Successfully.');
    }
}
