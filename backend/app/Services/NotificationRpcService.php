<?php

namespace App\Services;

use App\Models\User;

/**
 * RPC-методы уведомлений, вызываемые через Centrifugo.
 */
class NotificationRpcService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{items: list<array<string, mixed>>, next_cursor: string|null, unread_count: int}
     */
    public function list(User $user, array $data): array
    {
        return $this->notifications->list($user, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{unread_count: int}
     */
    public function read(User $user, array $data): array
    {
        return $this->notifications->markRead($user, $data);
    }

    /**
     * @return array{unread_count: int}
     */
    public function unreadCount(User $user): array
    {
        return [
            'unread_count' => $this->notifications->unreadCount($user),
        ];
    }
}
