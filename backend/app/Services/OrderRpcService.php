<?php

namespace App\Services;

use App\Enums\OrderExecutingStatus;
use App\Http\Resources\OrderExecutingResource;
use App\Http\Resources\OrderResource;
use App\Jobs\ModerateOrderJob;
use App\Models\Order;
use App\Models\User;
use App\Services\Centrifugo\CentrifugoClient;
use App\Services\Centrifugo\CentrifugoTokenService;
use Illuminate\Http\Request;

/**
 * RPC-методы заказов, вызываемые через Centrifugo.
 */
class OrderRpcService
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly OrderExecutingService $executing,
        private readonly NotificationService $notifications,
        private readonly CentrifugoClient $centrifugo,
        private readonly CentrifugoTokenService $tokens,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(User $user, array $data): array
    {
        $order = $this->orders->create($user, $data);
        $payload = $this->toArray($order);

        $this->centrifugo->publish(
            $this->tokens->personalChannel($user),
            [
                'type' => 'order.created',
                'order' => $payload,
            ],
        );

        ModerateOrderJob::dispatch($order->id);

        return $payload;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function mine(User $user): array
    {
        return $this->orders->listMine($user)
            ->map(fn ($order) => $this->toArray($order))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{items: list<array<string, mixed>>, next_cursor: int|null}
     */
    public function history(User $user, array $data): array
    {
        $page = $this->orders->listHistory($user, $data);

        return [
            'items' => $page['items']
                ->map(fn ($order) => $this->toArray($order))
                ->values()
                ->all(),
            'next_cursor' => $page['next_cursor'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function feed(): array
    {
        return $this->orders->listFeed()
            ->map(fn ($order) => $this->toArray($order))
            ->values()
            ->all();
    }

    /**
     * Берёт заказ в работу и открывает первую точку.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function start(User $user, array $data): array
    {
        $result = $this->executing->start($user, $data);

        $this->centrifugo->broadcast(
            [
                (string) config('centrifugo.channels.search'),
            ],
            [
                'type' => 'order.taken',
                'order_id' => $result->order_id,
            ],
        );

        if ($result->wasRecentlyCreated) {
            $this->notifications->notifyOrderTaken($result);
        }

        $this->publishOrderStatus($result->order);

        return $this->executingToArray($result);
    }

    /**
     * Снимок выполнения для автора (наблюдение за исполнителем).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function watching(User $user, array $data): array
    {
        return $this->executingToArray($this->executing->watching($user, $data));
    }

    /**
     * Есть ли у пользователя заказ в процессе (исполнитель или автор).
     *
     * @return array{order_id: int|null, view: 'execute'|'watch'|null}
     */
    public function active(User $user): array
    {
        $found = $this->executing->active($user);
        if ($found === null) {
            return [
                'order_id' => null,
                'view' => null,
            ];
        }

        return $found;
    }

    /**
     * Текущее выполнение заказа этим исполнителем.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function executing(User $user, array $data): array
    {
        return $this->executingToArray($this->executing->show($user, $data));
    }

    /**
     * Публикует координаты исполнителя в личный канал автора заказа.
     *
     * @param  array<string, mixed>  $data
     * @return array{ok: true}
     */
    public function location(User $user, array $data): array
    {
        $executing = $this->executing->updateLocation($user, $data);
        $author = $executing->order->user;

        if ($author) {
            $this->centrifugo->publish(
                $this->tokens->personalChannel($author),
                [
                    'type' => 'executor.location',
                    'order_id' => $executing->order_id,
                    'lat' => (float) $executing->lat,
                    'lon' => (float) $executing->lon,
                ],
            );
        }

        return ['ok' => true];
    }

    /**
     * Завершает точку маршрута и при необходимости весь заказ.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function completePoint(User $user, array $data): array
    {
        $result = $this->executing->completePoint($user, $data);
        $author = $result->order->user;

        if ($author) {
            $this->centrifugo->publish(
                $this->tokens->personalChannel($author),
                [
                    'type' => 'order.executing',
                    'executing' => $this->executingToArray($result),
                ],
            );
        }

        if ($result->status === OrderExecutingStatus::Complete) {
            $this->notifications->notifyOrderCompleted($result);
            $this->publishOrderStatus($result->order);
        }

        return $this->executingToArray($result);
    }

    /**
     * Обновляет статус заказа у автора в realtime.
     */
    private function publishOrderStatus(?Order $order): void
    {
        if (! $order) {
            return;
        }

        $order->loadMissing(['points', 'user', 'currentExecuting']);
        $author = $order->user;
        if (! $author) {
            return;
        }

        $this->centrifugo->publish(
            $this->tokens->personalChannel($author),
            [
                'type' => 'order.status',
                'order' => $this->toArray($order),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(mixed $order): array
    {
        return OrderResource::make($order)->resolve(new Request);
    }

    /**
     * @return array<string, mixed>
     */
    private function executingToArray(mixed $executing): array
    {
        return OrderExecutingResource::make($executing)->resolve(new Request);
    }
}
