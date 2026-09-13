<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Уведомление заказчику: исполнитель отказался от выполнения заказа.
 */
class OrderDeclinedNotification extends Notification
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
        return [
            'type' => 'order.declined',
            'title' => 'Исполнитель отказался',
            'body' => "Исполнитель {$this->executor->name} отказался от {$this->order->label()}. Заказ снова в поиске исполнителя.",
            'order_id' => $this->order->id,
            'executor_id' => $this->executor->id,
            'executor_name' => $this->executor->name,
        ];
    }
}
