<?php

namespace App\Models;

use Database\Factories\OrderPointFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Точка заказа: адрес, координаты, стоимость и порядок в маршруте.
 */
#[Fillable([
    'order_id',
    'description',
    'address',
    'lat',
    'lon',
    'position',
    'cost',
])]
class OrderPoint extends Model
{
    /** @use HasFactory<OrderPointFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lon' => 'decimal:7',
            'position' => 'integer',
            'cost' => 'decimal:2',
        ];
    }

    /**
     * Заказ, к которому относится точка.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Факты выполнения этой точки в назначениях исполнителей.
     */
    public function executingPoints(): HasMany
    {
        return $this->hasMany(OrderExecutingPoint::class);
    }
}
