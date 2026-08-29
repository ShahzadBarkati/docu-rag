<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Auth\Contracts\AuthenticationContract;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use Override;

final class AuthenticationService implements AuthenticationContract
{
    #[Override]
    public function register(array $credentials): array
    {
        $user = User::create($credentials);

        return [
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }

    #[Override]
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw new AuthenticationException('Invalid credentials.');
        }

        return [
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }

    #[Override]
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
