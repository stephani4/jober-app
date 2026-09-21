<?php

namespace App\Models;

use Database\Factories\OrderExecutingRatingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Оценка заказчиком качества выполнения назначения исполнителя.
 */
#[Fillable([
    'order_executing_id',
    'rating',
])]
class OrderExecutingRating extends Model
{
    /** @use HasFactory<OrderExecutingRatingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    /**
     * Назначение исполнителя, которое оценили.
     */
    public function orderExecuting(): BelongsTo
    {
        return $this->belongsTo(OrderExecuting::class);
    }
}
