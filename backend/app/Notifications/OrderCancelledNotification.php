<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Уведомление исполнителю: заказчик отменил заказ.
 */
class OrderCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order,
        public User $executor,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $author = $this->order->user?->name;

        return [
            'type' => 'order.cancelled',
            'title' => 'Заказ отменён',
            'body' => 'Заказчик '.($author ?: 'отменил заказ')
                ." отменил {$this->order->label()}. Выполнение остановлено.",
            'order_id' => $this->order->id,
            'executor_id' => $this->executor->id,
        ];
    }
}
