<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderMessageResource;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HTTP-маршруты чата заказа: список и отправка сообщений.
 */
class OrderMessageController extends Controller
{
    public function __construct(
        private readonly OrderMessageService $messages,
    ) {}

    /**
     * Сообщения заказа от старых к новым.
     */
    public function index(Request $request, Order $order): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $items = $this->messages->list($user, [
            'order_id' => $order->id,
        ]);

        return response()->json([
            'data' => OrderMessageResource::collection($items)->resolve($request),
        ]);
    }

    /**
     * Отправляет сообщение в чат заказа.
     */
    public function store(Request $request, Order $order): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $message = $this->messages->send($user, [
            'order_id' => $order->id,
            'body' => $request->input('body'),
        ]);

        return response()->json([
            'data' => OrderMessageResource::make($message)->resolve($request),
        ], 201);
    }
}
