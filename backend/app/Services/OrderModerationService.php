<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Moderation\OrderModerationRule;
use App\Services\Centrifugo\CentrifugoClient;
use App\Services\Centrifugo\CentrifugoTokenService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Traversable;

/**
 * Прогоняет заказ по правилам модерации и публикует результат в realtime.
 */
class OrderModerationService
{
    /**
     * @param  Traversable<int, OrderModerationRule>|array<int, OrderModerationRule>  $rules
     */
    public function __construct(
        private readonly iterable $rules,
        private readonly CentrifugoClient $centrifugo,
        private readonly CentrifugoTokenService $tokens,
    ) {}

    /**
     * Применяет правила к заказу в статусе moderate.
     */
    public function process(int $orderId): void
    {
        $order = Order::query()->with(['points', 'user', 'currentExecuting'])->find($orderId);
        if (! $order || $order->status !== OrderStatus::Moderate) {
            return;
        }

        foreach ($this->rules as $rule) {
            $reason = $rule->evaluate($order);
            if ($reason !== null) {
                $this->applyCancel($order, $reason);

                return;
            }
        }

        $this->applyApprove($order);
    }

    /**
     * Ручное одобрение заказа, который ещё на модерации.
     */
    public function approve(Order $order): Order
    {
        $this->assertModerate($order);

        return $this->applyApprove($order);
    }

    /**
     * Ручной отказ с причиной. Тот же realtime-путь, что и у автомодерации.
     */
    public function reject(Order $order, string $reason): Order
    {
        $this->assertModerate($order);

        return $this->applyCancel($order, $reason);
    }

    private function assertModerate(Order $order): void
    {
        if ($order->status !== OrderStatus::Moderate) {
            throw ValidationException::withMessages([
                'order' => ['Заказ не находится на модерации.'],
            ]);
        }
    }

    private function applyApprove(Order $order): Order
    {
        $order->update([
            'status' => OrderStatus::Wait,
            'reason' => null,
        ]);
        $fresh = $order->fresh(['points', 'user', 'currentExecuting']);
        $this->publish($fresh);

        return $fresh ?? $order;
    }

    private function applyCancel(Order $order, string $reason): Order
    {
        $order->update([
            'status' => OrderStatus::Cancel,
            'reason' => $reason,
        ]);
        $fresh = $order->fresh(['points', 'user', 'currentExecuting']);
        $this->publish($fresh);

        return $fresh ?? $order;
    }

    /**
     * Сообщает автору результат и, при одобрении, кладёт заказ в ленту поиска.
     */
    private function publish(?Order $order): void
    {
        if (! $order) {
            return;
        }

        $payload = OrderResource::make($order)->resolve(new Request);
        $author = $order->user;
        if ($author) {
            $this->centrifugo->publish(
                $this->tokens->personalChannel($author),
                [
                    'type' => 'order.moderated',
                    'order' => $payload,
                ],
            );
            $this->centrifugo->publish(
                $this->tokens->personalChannel($author),
                [
                    'type' => 'order.status',
                    'order' => $payload,
                ],
            );
        }

        if ($order->status === OrderStatus::Wait) {
            $this->centrifugo->broadcast(
                [
                    (string) config('centrifugo.channels.search'),
                ],
                [
                    'type' => 'order.created',
                    'order' => $payload,
                ],
            );
        }
    }
}
