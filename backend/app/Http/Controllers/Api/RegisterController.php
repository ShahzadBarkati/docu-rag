<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\Contracts\AuthenticationContract;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final readonly class RegisterController
{
    public function __construct(private readonly AuthenticationContract $auth) {}

    public function __invoke(RegisterRequest $request): JsonResponse
    {
        return ApiResponse::success($this->auth->register($request->validated()), 'Registration successful!', 201);
    }
}
