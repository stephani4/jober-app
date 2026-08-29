<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Уведомление о завершении заказа исполнителем.
 */
class OrderCompletedNotification extends Notification
{
    use Queueable;

    /**
     * @param  'author'|'executor'  $audience
     */
    public function __construct(
        public Order $order,
        public User $executor,
        public string $audience = 'author',
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
        $label = $this->order->label();

        return [
            'type' => 'order.completed',
            'title' => 'Заказ выполнен',
            'body' => $this->audience === 'executor'
                ? "Вы завершили {$label}."
                : "Исполнитель {$this->executor->name} завершил {$label}.",
            'order_id' => $this->order->id,
            'executor_id' => $this->executor->id,
        ];
    }
}
