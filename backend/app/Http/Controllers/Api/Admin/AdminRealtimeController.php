<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Centrifugo\CentrifugoTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Выдаёт connection JWT для Centrifugo: админ подписывается на канал наблюдения за заказом.
 */
class AdminRealtimeController extends Controller
{
    public function __construct(
        private readonly CentrifugoTokenService $tokens,
    ) {}

    /**
     * Токен подключения; с order_id — подписка на канал наблюдения за заказом.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['nullable', 'integer', 'min:1', 'exists:orders,id'],
        ]);

        $admin = $request->user();

        return response()->json([
            'token' => $this->tokens->issueForAdmin($admin, $validated['order_id'] ?? null),
        ]);
    }
}
