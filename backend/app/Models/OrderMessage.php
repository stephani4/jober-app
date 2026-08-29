<?php

namespace App\Models;

use Database\Factories\OrderMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Сообщение чата в рамках заказа.
 */
#[Fillable([
    'order_id',
    'user_id',
    'body',
])]
class OrderMessage extends Model
{
    /** @use HasFactory<OrderMessageFactory> */
    use HasFactory;

    /**
     * Заказ, к которому относится сообщение.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Автор сообщения.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
