<?php

namespace App\Services\Admin;

use App\Http\Resources\AdminResource;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * JWT-аутентификация сотрудников админки (guard=admin).
 */
class AdminAuthService
{
    /**
     * @param  array{login: string, password: string}  $credentials
     * @return array{token: string, token_type: string, expires_in: int, admin: Admin}
     */
    public function login(array $credentials): array
    {
        $token = Auth::guard('admin')->attempt([
            'email' => $credentials['login'],
            'password' => $credentials['password'],
        ]);

        if (! $token) {
            throw ValidationException::withMessages([
                'login' => ['Неверный логин или пароль.'],
            ]);
        }

        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        return $this->tokenPayload($token, $admin);
    }

    public function me(): ?Admin
    {
        /** @var Admin|null $admin */
        $admin = Auth::guard('admin')->user();

        return $admin;
    }

    public function logout(): void
    {
        Auth::guard('admin')->logout();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Admin $admin): array
    {
        return AdminResource::make($admin)->resolve(new Request);
    }

    /**
     * @return array{token: string, token_type: string, expires_in: int, admin: Admin}
     */
    private function tokenPayload(string $token, Admin $admin): array
    {
        return [
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => (int) config('jwt.ttl') * 60,
            'admin' => $admin,
        ];
    }
}
