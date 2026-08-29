<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'Регистрация успешна. Войдите в аккаунт.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->value,
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $payload = $this->authService->login($request->validated());

        return response()->json([
            'token' => $payload['token'],
            'token_type' => $payload['token_type'],
            'expires_in' => $payload['expires_in'],
            'user' => [
                'id' => $payload['user']->id,
                'name' => $payload['user']->name,
                'email' => $payload['user']->email,
                'role' => $payload['user']->role?->value,
                'birth_date' => $payload['user']->birth_date?->toDateString(),
            ],
        ]);
    }

    public function me(): JsonResponse
    {
        $user = $this->authService->me();

        return response()->json([
            'id' => $user?->id,
            'name' => $user?->name,
            'email' => $user?->email,
            'role' => $user?->role?->value,
            'birth_date' => $user?->birth_date?->toDateString(),
        ]);
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json([
            'message' => 'Вы вышли из системы.',
        ]);
    }
}
