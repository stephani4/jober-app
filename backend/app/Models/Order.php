<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Заказ, опубликованный пользователем.
 */
#[Fillable([
    'user_id',
    'description',
    'cost',
    'status',
    'reason',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'status' => OrderStatus::class,
        ];
    }

    /**
     * Автор заказа.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Точки маршрута заказа, по возрастанию position.
     */
    public function points(): HasMany
    {
        return $this->hasMany(OrderPoint::class)->orderBy('position');
    }

    /**
     * Назначения исполнителей на этот заказ.
     */
    public function executings(): HasMany
    {
        return $this->hasMany(OrderExecuting::class);
    }

    /**
     * Сообщения чата этого заказа.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(OrderMessage::class);
    }

    /**
     * Последнее назначение исполнителя.
     */
    public function currentExecuting(): HasOne
    {
        return $this->hasOne(OrderExecuting::class)->latestOfMany();
    }

    /**
     * Короткая подпись заказа для уведомлений.
     */
    public function label(): string
    {
        $description = trim((string) $this->description);
        if ($description === '') {
            return 'заказ';
        }

        $short = mb_strlen($description) > 80
            ? mb_substr($description, 0, 77).'…'
            : $description;

        return "«{$short}»";
    }
}
