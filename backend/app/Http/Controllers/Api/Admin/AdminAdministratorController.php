<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminResource;
use App\Models\Admin;
use App\Services\Admin\AdminAdministratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HTTP-контур сотрудников админки.
 */
class AdminAdministratorController extends Controller
{
    public function __construct(
        private readonly AdminAdministratorService $administrators,
    ) {}

    /**
     * Справочник ролей и разрешений для формы.
     */
    public function catalog(): JsonResponse
    {
        return response()->json($this->administrators->catalog());
    }

    /**
     * Список сотрудников с фильтрами по имени, email и роли; по 15 на страницу.
     */
    public function index(Request $request): JsonResponse
    {
        $page = $this->administrators->list($request->all());

        return response()->json([
            'items' => $page['items']
                ->map(fn (Admin $admin) => AdminResource::make($admin)->resolve($request))
                ->values()
                ->all(),
            'next_cursor' => $page['next_cursor'],
        ]);
    }

    /**
     * Карточка сотрудника.
     */
    public function show(Request $request, Admin $admin): JsonResponse
    {
        return response()->json(
            AdminResource::make($this->administrators->find($admin))->resolve($request),
        );
    }

    /**
     * Создание сотрудника.
     */
    public function store(Request $request): JsonResponse
    {
        $admin = $this->administrators->create($request->all());

        return response()->json(
            AdminResource::make($admin)->resolve($request),
            201,
        );
    }

    /**
     * Обновление сотрудника.
     */
    public function update(Request $request, Admin $admin): JsonResponse
    {
        $admin = $this->administrators->update($admin, $request->all());

        return response()->json(
            AdminResource::make($admin)->resolve($request),
        );
    }

    /**
     * Удаление сотрудника.
     */
    public function destroy(Request $request, Admin $admin): JsonResponse
    {
        /** @var Admin $actor */
        $actor = $request->user();
        $this->administrators->delete($admin, $actor);

        return response()->json(null, 204);
    }
}
