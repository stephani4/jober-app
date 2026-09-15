<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Admin\AdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HTTP-контур пользователей для админки.
 */
class AdminUserController extends Controller
{
    public function __construct(
        private readonly AdminUserService $users,
    ) {}

    /**
     * Список пользователей с фильтрами по имени, email дате рождения и роли; по 15 на страницу.
     */
    public function index(Request $request): JsonResponse
    {
        $page = $this->users->list($request->all());

        return response()->json([
            'items' => $page['items']
                ->map(fn (User $user) => UserResource::make($user)->resolve($request))
                ->values()
                ->all(),
            'next_cursor' => $page['next_cursor'],
        ]);
    }
}
