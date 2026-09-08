<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderTypeResource;
use App\Services\OrderTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HTTP-справочник видов заказа для формы публикации.
 */
class OrderTypeController extends Controller
{
    public function __construct(
        private readonly OrderTypeService $types,
    ) {}

    /**
     * Список видов заказа для формы публикации.
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
