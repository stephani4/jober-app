<?php

namespace App\Moderation;

use App\Models\Order;

/**
 * Правило автоматической модерации заказа.
 * Новые правила регистрируются тегом `order.moderation` в AppServiceProvider.
 */
interface OrderModerationRule
{
    /**
     * Возвращает причину отказа или null, если правило пройдено.
     */
    public function evaluate(Order $order): ?string;
}
