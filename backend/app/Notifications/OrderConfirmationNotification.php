<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\OrderExecuting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Уведомление заказчику: исполнитель завершил заказ, сообщите код подтверждения.
 */
class OrderConfirmationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order,
        public OrderExecuting $executing,
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
        $executor = $this->executing->executor;
        $code = (string) $this->executing->confirmation_number;

        return [
            'type' => 'order.confirmation',
            'title' => 'Заказ выполнен',
            'body' => "Исполнитель {$executor?->name} завершил {$this->order->label()}. Сообщите код подтверждения: {$code}.",
            'order_id' => $this->order->id,
            'executor_id' => $executor?->id,
            'executor_name' => $executor?->name,
            'confirmation_number' => $code,
        ];
    }
}
