<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Admin\AdminOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HTTP-контур ручной модерации заказов.
 */
class AdminOrderController extends Controller
{
    public function __construct(
        private readonly AdminOrderService $orders,
    ) {}

    /**
     * Страница заказов для модерации.
     */
    public function index(Request $request): JsonResponse
    {
        $page = $this->orders->list($request->all());

        return response()->json([
            'items' => $page['items']
                ->map(fn (Order $order) => OrderResource::make($order)->resolve($request))
                ->values()
                ->all(),
            'next_cursor' => $page['next_cursor'],
        ]);
    }

    /**
     * Одобряет заказ: moderate → wait.
     */
    public function approve(Request $request, Order $order): JsonResponse
    {
        $updated = $this->orders->approve($order);

        return response()->json([
            'order' => OrderResource::make($updated)->resolve($request),
        ]);
    }

    /**
     * Отклоняет заказ с причиной.
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        $updated = $this->orders->cancel($order, $request->all());

        return response()->json([
            'order' => OrderResource::make($updated)->resolve($request),
        ]);
    }
}
