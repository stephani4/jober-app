<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\AdminResource;
use App\Models\Admin;
use App\Services\Admin\AdminAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Логин, профиль и выход сотрудника админки.
 */
class AdminAuthController extends Controller
{
    public function __construct(
        private readonly AdminAuthService $auth,
    ) {}

    /**
     * JWT-логин сотрудника.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $payload = $this->auth->login($request->validated());

        return response()->json([
            'token' => $payload['token'],
            'token_type' => $payload['token_type'],
            'expires_in' => $payload['expires_in'],
            'admin' => AdminResource::make($payload['admin'])->resolve($request),
        ]);
    }

    /**
     * Текущий сотрудник и его роли/права.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        return response()->json(AdminResource::make($admin)->resolve($request));
    }

    /**
     * Инвалидирует JWT админки.
     */
    public function logout(): JsonResponse
    {
        $this->auth->logout();

        return response()->json([
            'message' => 'Вы вышли из системы.',
        ]);
    }
}
