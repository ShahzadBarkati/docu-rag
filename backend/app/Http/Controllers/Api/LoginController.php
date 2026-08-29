<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\Contracts\AuthenticationContract;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final readonly class LoginController
{
    public function __construct(private readonly AuthenticationContract $auth) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        return ApiResponse::success($this->auth->login($request->validated()), 'Login Successful!');
    }
}
