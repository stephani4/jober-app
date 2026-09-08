<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * VAPID-ключ и Web Push-подписки устройства.
 */
class PushSubscriptionController extends Controller
{
    public function __construct(
        private readonly WebPushService $webPush,
    ) {}

    /**
     * Публичный VAPID-ключ для PushManager.subscribe.
     */
    public function vapid(): JsonResponse
    {
        return response()->json($this->webPush->vapidPublic());
    }

    /**
     * Сохраняет или обновляет подписку текущего пользователя.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $this->webPush->subscribe($user, $request->all());

        return response()->json(['ok' => true]);
    }

    /**
     * Снимает подписку текущего устройства.
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $this->webPush->unsubscribe($user, $request->all());

        return response()->json(['ok' => true]);
    }
}
