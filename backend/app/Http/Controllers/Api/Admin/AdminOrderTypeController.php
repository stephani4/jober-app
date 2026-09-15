<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderTypeResource;
use App\Services\OrderTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Справочник видов заказа для фильтров админки.
 */
class AdminOrderTypeController extends Controller
{
    public function __construct(
        private readonly OrderTypeService $types,
    ) {}

    /**
     * Список видов заказа.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'types' => $this->types->list()
                ->map(fn ($type) => OrderTypeResource::make($type)->resolve($request))
                ->values()
                ->all(),
        ]);
    }
}
