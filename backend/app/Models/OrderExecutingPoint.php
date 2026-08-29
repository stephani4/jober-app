<?php

namespace App\Models;

use App\Enums\OrderExecutingStatus;
use Database\Factories\OrderExecutingPointFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Статус выполнения конкретной точки заказа в рамках назначения.
 */
#[Fillable([
    'order_executing_id',
    'order_point_id',
    'status',
    'process_at',
    'complete_at',
])]
class OrderExecutingPoint extends Model
{
    /** @use HasFactory<OrderExecutingPointFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => OrderExecutingStatus::class,
            'process_at' => 'datetime',
            'complete_at' => 'datetime',
        ];
    }

    /**
     * Назначение исполнителя, в рамках которого выполняется точка.
     */
    public function orderExecuting(): BelongsTo
    {
        return $this->belongsTo(OrderExecuting::class);
    }

    /**
     * Точка заказа, которую нужно выполнить.
     */
    public function orderPoint(): BelongsTo
    {
        return $this->belongsTo(OrderPoint::class);
    }
}
