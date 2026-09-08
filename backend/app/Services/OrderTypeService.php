<?php

namespace App\Services;

use App\Models\OrderType;
use Illuminate\Support\Collection;

/**
 * Справочник видов заказа.
 */
class OrderTypeService
{
    /**
     * @return Collection<int, OrderType>
     */
    public function list(): Collection
    {
        return OrderType::query()->orderBy('id')->get();
    }
}
