<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    /**
     * @param  array{name: string, email: string, password: string, birth_date: string, role: string}  $data
     */
    public function register(array $data): User
    {
        return User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'birth_date' => $data['birth_date'],
            'role' => UserRole::from($data['role']),
            'personal_data_consent_at' => now(),
        ]);
    }

    /**
     * @param  array{login: string, password: string}  $credentials
     * @return array{token: string, token_type: string, expires_in: int, user: User}
     */
    public function login(array $credentials): array
    {
        $token = Auth::guard('api')->attempt([
            'email' => $credentials['login'],
            'password' => $credentials['password'],
        ]);

        if (! $token) {
            throw ValidationException::withMessages([
                'login' => ['Неверный логин или пароль.'],
            ]);
        }

        /** @var User $user */
        $user = Auth::guard('api')->user();

        return $this->tokenPayload($token, $user);
    }

    public function me(): ?User
    {
        /** @var User|null $user */
        $user = Auth::guard('api')->user();

        return $user;
    }

    public function logout(): void
    {
        Auth::guard('api')->logout();
    }

    /**
     * @return array{token: string, token_type: string, expires_in: int, user: User}
     */
    public function refresh(): array
    {
        $token = JWTAuth::parseToken()->refresh();

        /** @var User $user */
        $user = Auth::guard('api')->setToken($token)->user();

        return $this->tokenPayload($token, $user);
    }

    /**
     * @return array{token: string, token_type: string, expires_in: int, user: User}
     */
    private function tokenPayload(string $token, User $user): array
    {
        return [
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => (int) config('jwt.ttl') * 60,
            'user' => $user,
        ];
    }
}
