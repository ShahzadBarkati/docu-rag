<?php

namespace App\Services\Auth\Contracts;

use App\Models\User;

interface AuthenticationContract
{
    public function register(array $credentials): array;

    public function login(array $credentials): array;

    public function logout(User $user): void;
}
