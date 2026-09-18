<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderExecutingResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Admin\AdminOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HTTP-контур заказов для админки: списки, модерация, страница заказа.
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
     * Страница заказа /admin/orders/{id}/show: общая информация.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        return response()->json([
            'order' => OrderResource::make($this->orders->show($order))->resolve($request),
        ]);
    }

    /**
     * Текущее выполнение заказа для вкладки «Наблюдение» (null, пока не взято в работу).
     */
    public function executing(Request $request, Order $order): JsonResponse
    {
        $executing = $this->orders->watching($order);

        return response()->json([
            'executing' => $executing !== null
                ? OrderExecutingResource::make($executing)->resolve($request)
                : null,
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
