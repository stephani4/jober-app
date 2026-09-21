<?php

namespace App\Models;

use App\Enums\OrderExecutingStatus;
use Database\Factories\OrderExecutingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Назначение исполнителя на заказ и статус его выполнения.
 */
#[Fillable([
    'order_id',
    'executor_id',
    'status',
    'process_at',
    'complete_at',
    'lat',
    'lon',
    'location_at',
    'canceled_at',
    'confirmation_at',
    'confirmation_number',
])]
class OrderExecuting extends Model
{
    /** @use HasFactory<OrderExecutingFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => OrderExecutingStatus::class,
            'process_at' => 'datetime',
            'complete_at' => 'datetime',
            'lat' => 'float',
            'lon' => 'float',
            'location_at' => 'datetime',
            'confirmation_at' => 'datetime',
        ];
    }

    /**
     * Заказ, который выполняет исполнитель.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Исполнитель, назначенный на заказ.
     */
    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executor_id');
    }

    /**
     * Точки этого назначения и их статусы.
     */
    public function points(): HasMany
    {
        return $this->hasMany(OrderExecutingPoint::class);
    }

    /**
     * Оценка автора за это выполнение; нет записи — рейтинг ещё не ставили (null).
     */
    public function rating(): HasOne
    {
        return $this->hasOne(OrderExecutingRating::class);
    }

    /**
     * Можно ли сейчас поставить оценку: этап confirmation или текущие сутки после complete.
     */
    public function canAcceptRating(): bool
    {
        if ($this->ratingValue() !== null) {
            return false;
        }

        if ($this->status === OrderExecutingStatus::Confirmation) {
            return true;
        }

        if ($this->status !== OrderExecutingStatus::Complete) {
            return false;
        }

        $anchor = $this->confirmation_at ?? $this->complete_at;
        if ($anchor === null) {
            return false;
        }

        return $anchor->isSameDay(now());
    }

    /**
     * Поставленная оценка или null, если запись ещё не создавали.
     */
    public function ratingValue(): ?int
    {
        $this->loadMissing('rating');
        $value = $this->rating?->rating;

        return $value !== null ? (int) $value : null;
    }
}
