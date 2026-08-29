<?php

namespace App\Services;

use App\Http\Resources\NotificationResource;
use App\Models\OrderExecuting;
use App\Models\User;
use App\Notifications\OrderCompletedNotification;
use App\Notifications\OrderTakenNotification;
use App\Services\Centrifugo\CentrifugoClient;
use App\Services\Centrifugo\CentrifugoTokenService;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Сохранение, выборка и пометка уведомлений; публикация в личный канал.
 */
class NotificationService
{
    public const PAGE_SIZE = 15;

    public function __construct(
        private readonly CentrifugoClient $centrifugo,
        private readonly CentrifugoTokenService $tokens,
    ) {}

    /**
     * Пишет уведомление заказчику: кто взял заказ в работу.
     */
    public function notifyOrderTaken(OrderExecuting $executing): void
    {
        $executing->loadMissing(['order.user', 'executor']);

        $order = $executing->order;
        $executor = $executing->executor;
        if (! $order?->user || ! $executor || $order->user->id === $executor->id) {
            return;
        }

        $this->send($order->user, new OrderTakenNotification($order, $executor));
    }

    /**
     * Пишет уведомления автору заказа и исполнителю, затем пушит их в realtime.
     */
    public function notifyOrderCompleted(OrderExecuting $executing): void
    {
        $executing->loadMissing(['order.user', 'executor']);

        $order = $executing->order;
        $executor = $executing->executor;
        if (! $order?->user || ! $executor) {
            return;
        }

        $this->send($order->user, new OrderCompletedNotification($order, $executor, 'author'));

        if ($order->user->id !== $executor->id) {
            $this->send($executor, new OrderCompletedNotification($order, $executor, 'executor'));
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{items: list<array<string, mixed>>, next_cursor: string|null, unread_count: int}
     */
    public function list(User $user, array $data): array
    {
        $payload = $this->validateList($data);
        $limit = self::PAGE_SIZE;

        $query = $user->notifications()->orderByDesc('created_at')->orderByDesc('id');

        if ($payload['filter'] === 'unread') {
            $query->whereNull('read_at');
        }

        if ($payload['cursor']) {
            $cursor = $user->notifications()->find($payload['cursor']);
            if ($cursor) {
                $query->where(function ($inner) use ($cursor) {
                    $inner->where('created_at', '<', $cursor->created_at)
                        ->orWhere(function ($sameTime) use ($cursor) {
                            $sameTime->where('created_at', $cursor->created_at)
                                ->where('id', '<', $cursor->id);
                        });
                });
            }
        }

        $rows = $query->limit($limit + 1)->get();
        $hasMore = $rows->count() > $limit;
        $page = $hasMore ? $rows->take($limit)->values() : $rows->values();

        return [
            'items' => NotificationResource::collection($page)->resolve(new Request),
            'next_cursor' => $hasMore ? (string) $page->last()->id : null,
            'unread_count' => $this->unreadCount($user),
        ];
    }

    /**
     * Помечает видимые уведомления прочитанными.
     *
     * @param  array<string, mixed>  $data
     * @return array{unread_count: int}
     */
    public function markRead(User $user, array $data): array
    {
        $payload = $this->validateRead($data);

        if ($payload['ids'] !== []) {
            $user->notifications()
                ->whereIn('id', $payload['ids'])
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return [
            'unread_count' => $this->unreadCount($user),
        ];
    }

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    private function send(User $user, Notification $notification): void
    {
        $user->notify($notification);

        /** @var DatabaseNotification|null $record */
        $record = $user->notifications()->latest('created_at')->first();
        if (! $record) {
            return;
        }

        $this->publishCreated($user, $record);
    }

    private function publishCreated(User $user, DatabaseNotification $record): void
    {
        try {
            $this->centrifugo->publish(
                $this->tokens->personalChannel($user),
                [
                    'type' => 'notification.created',
                    'notification' => NotificationResource::make($record)->resolve(new Request),
                    'unread_count' => $this->unreadCount($user),
                ],
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{filter: string, cursor: string|null}
     */
    private function validateList(array $data): array
    {
        $validator = Validator::make($data, [
            'filter' => ['nullable', 'in:all,unread'],
            'cursor' => ['nullable', 'uuid'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        /** @var array{filter?: string, cursor?: string|null} $validated */
        $validated = $validator->validated();

        return [
            'filter' => $validated['filter'] ?? 'all',
            'cursor' => $validated['cursor'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{ids: list<string>}
     */
    private function validateRead(array $data): array
    {
        $validator = Validator::make($data, [
            'ids' => ['required', 'array', 'max:50'],
            'ids.*' => ['uuid'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        /** @var array{ids: list<string>} $validated */
        $validated = $validator->validated();

        return $validated;
    }
}
