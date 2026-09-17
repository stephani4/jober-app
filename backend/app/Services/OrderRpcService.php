<?php

namespace App\Services;

use App\Enums\OrderExecutingStatus;
use App\Enums\UserRole;
use App\Http\Resources\OrderExecutingResource;
use App\Http\Resources\OrderResource;
use App\Jobs\ModerateOrderJob;
use App\Models\Order;
use App\Models\OrderExecuting;
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
            ->map(fn ($order) => $this->toArrayForAuthor($order))
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
        return $this->executingToArrayForAuthor($this->executing->watching($user, $data));
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
                    'executing' => $this->executingToArrayForAuthor($result),
                ],
            );
        }

        if ($result->status === OrderExecutingStatus::Complete) {
            $this->notifications->notifyOrderCompleted($result);
            $this->publishOrderStatus($result->order);
        }

        if ($result->status === OrderExecutingStatus::Confirmation) {
            $this->notifications->notifyOrderConfirmation($result);
            $this->publishOrderStatus($result->order);
        }

        return $this->executingToArray($result);
    }

    /**
     * Подтверждает завершение заказа по коду от автора и уведомляет участников.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function confirm(User $executor, array $data): array
    {
        $executing = $this->executing->confirm($executor, $data);

        $this->notifications->notifyOrderCompleted($executing);
        $this->publishOrderStatus($executing->order);

        return $this->executingToArray($executing);
    }

    /**
     * Отменяет заказ по решению автора и уведомляет исполнителя, если он был назначен.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function cancel(User $user, array $data): array
    {
        ['order' => $order, 'executing' => $executing] = $this->executing->cancel($user, $data);

        if ($executing) {
            $this->notifications->notifyOrderCancelled($order, $executing);

            $executor = $executing->executor;
            if ($executor) {
                $this->centrifugo->publish(
                    $this->tokens->personalChannel($executor),
                    [
                        'type' => 'order.cancelled',
                        'order_id' => $order->id,
                        'order' => $this->toArray($order),
                    ],
                );
            }
        }

        $this->publishOrderStatus($order);

        // Всем клиентам: отменённый заказ исчезает из лент поиска и списков заказов.
        $this->centrifugo->broadcast(
            [
                (string) config('centrifugo.channels.search'),
            ],
            [
                'type' => 'order.status',
                'order' => $this->toArray($order),
            ],
        );

        return $this->toArray($order);
    }

    /**
     * Отказ исполнителя: уведомляет заказчика и возвращает заказ в ленту поиска.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function decline(User $executor, array $data): array
    {
        ['order' => $order, 'executing' => $executing] = $this->executing->decline($executor, $data);

        $this->notifications->notifyOrderDeclined($order, $executing);

        $author = $order->user;
        if ($author) {
            // Заказчику: realtime-тост и закрытие экрана наблюдения.
            $this->centrifugo->publish(
                $this->tokens->personalChannel($author),
                [
                    'type' => 'order.declined',
                    'order_id' => $order->id,
                    'order' => $this->toArray($order),
                ],
            );
        }

        // Заказ снова в поиске: лента обновляется, свободные исполнители получают оффер.
        $this->centrifugo->broadcast(
            [
                (string) config('centrifugo.channels.search'),
            ],
            [
                'type' => 'order.created',
                'order' => $this->toArray($order),
            ],
        );

        return $this->executingToArray($executing);
    }

    /**
     * Количество исполнителей онлайн без активного заказа.
     */
    public function availableExecutorsCount(): int
    {
        return User::query()
            ->where('role', UserRole::Executor)
            ->whereDoesntHave('orderExecutings', function ($query) {
                $query->where('status', OrderExecutingStatus::Process);
            })
            ->count();
    }

    /**
     * Количество откликов исполнителя за сегодня.
     */
    public function responsesCount(User $user): int
    {
        return OrderExecuting::query()
            ->where('executor_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();
    }

    /**
     * Обновляет статус заказа у автора в realtime.
     */
    private function publishOrderStatus(?Order $order): void
    {
        if (! $order) {
            return;
        }

        $order->loadMissing(['points', 'user', 'currentExecuting.executor.avatar']);
        $author = $order->user;
        if (! $author) {
            return;
        }

        $this->centrifugo->publish(
            $this->tokens->personalChannel($author),
            [
                'type' => 'order.status',
                'order' => $this->toArrayForAuthor($order),
            ],
        );
    }

    /**
     * Данные заказа для автора; включает код подтверждения на этапе confirmation.
     *
     * @return array<string, mixed>
     */
    private function toArrayForAuthor(mixed $order): array
    {
        $data = $this->toArray($order);

        $executing = $order->currentExecuting;
        if ($executing?->status === OrderExecutingStatus::Confirmation) {
            $data['confirmation_number'] = (string) $executing->confirmation_number;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(mixed $order): array
    {
        return OrderResource::make($order)->resolve(new Request);
    }

    /**
     * Данные выполнения для автора; на этапе confirmation добавляет код подтверждения.
     *
     * @return array<string, mixed>
     */
    private function executingToArrayForAuthor(OrderExecuting $executing): array
    {
        $payload = $this->executingToArray($executing);

        // Код знает только автор: в ответах исполнителю он не отправляется.
        if ($executing->status === OrderExecutingStatus::Confirmation) {
            $payload['order']['confirmation_number'] = (string) $executing->confirmation_number;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function executingToArray(mixed $executing): array
    {
        return OrderExecutingResource::make($executing)->resolve(new Request);
    }
}
