<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Отправляет Web Push на устройства пользователя.
 */
class SendWebPushJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{title: string, body: string, url?: string, tag?: string, notification_id?: string, order_id?: int|null}  $payload
     */
    public function __construct(
        public int $userId,
        public array $payload,
    ) {}

    public function handle(WebPushService $webPush): void
    {
        $user = User::query()->find($this->userId);
        if (! $user) {
            return;
        }

        $webPush->sendToUser($user, $this->payload);
    }
}
