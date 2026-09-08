<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Вид заказа: определяет сценарий публикации (точки маршрута, формулировки).
 */
#[Fillable([
    'name',
    'description',
])]
class OrderType extends Model
{
    public const BUY_AND_DELIVER = 1;

    public const HELP_CARRY = 2;

    public const ERRAND = 3;

    public const DEFAULT_MAX_POINTS = 20;

    /**
     * Заказы этого вида.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Для «Купим и привезем» достаточно одной точки доставки.
     */
    public function isSinglePoint(): bool
    {
        return $this->id === self::BUY_AND_DELIVER;
    }

    public function maxPoints(): int
    {
        return $this->isSinglePoint() ? 1 : self::DEFAULT_MAX_POINTS;
    }
}
