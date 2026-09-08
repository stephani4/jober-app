<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

/**
 * Подписки устройств и отправка Web Push.
 */
class WebPushService
{
    public function enabled(): bool
    {
        return filled(config('webpush.vapid.public_key'))
            && filled(config('webpush.vapid.private_key'));
    }

    /**
     * @return array{enabled: bool, public_key: string|null}
     */
    public function vapidPublic(): array
    {
        return [
            'enabled' => $this->enabled(),
            'public_key' => $this->enabled() ? (string) config('webpush.vapid.public_key') : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function subscribe(User $user, array $data): PushSubscription
    {
        $payload = $this->validateSubscribe($data);

        return PushSubscription::query()->updateOrCreate(
            ['endpoint' => $payload['endpoint']],
            [
                'user_id' => $user->id,
                'public_key' => $payload['keys']['p256dh'],
                'auth_token' => $payload['keys']['auth'],
                'content_encoding' => $payload['content_encoding'],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function unsubscribe(User $user, array $data): void
    {
        $payload = $this->validateUnsubscribe($data);

        PushSubscription::query()
            ->where('user_id', $user->id)
            ->where('endpoint', $payload['endpoint'])
            ->delete();
    }

    /**
     * @param  array{title: string, body: string, url?: string, tag?: string, notification_id?: string, order_id?: int|null}  $payload
     */
    public function sendToUser(User $user, array $payload): void
    {
        if (! $this->enabled()) {
            return;
        }

        $subscriptions = $user->pushSubscriptions()->get();
        if ($subscriptions->isEmpty()) {
            return;
        }

        try {
            $webPush = $this->client();
        } catch (Throwable $exception) {
            report($exception);

            return;
        }

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        foreach ($subscriptions as $subscription) {
            try {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $subscription->endpoint,
                        'publicKey' => $subscription->public_key,
                        'authToken' => $subscription->auth_token,
                        'contentEncoding' => $subscription->content_encoding ?: 'aes128gcm',
                    ]),
                    $body,
                );
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        try {
            foreach ($webPush->flush() as $report) {
                if ($report->isSubscriptionExpired()) {
                    PushSubscription::query()->where('endpoint', $report->getEndpoint())->delete();
                }
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * @throws \ErrorException
     */
    private function client(): WebPush
    {
        return new WebPush([
            'VAPID' => [
                'subject' => (string) config('webpush.vapid.subject'),
                'publicKey' => (string) config('webpush.vapid.public_key'),
                'privateKey' => (string) config('webpush.vapid.private_key'),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{endpoint: string, keys: array{p256dh: string, auth: string}, content_encoding: string}
     */
    private function validateSubscribe(array $data): array
    {
        $validator = Validator::make($data, [
            'endpoint' => ['required', 'string', 'max:2048'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'content_encoding' => ['nullable', 'string', 'in:aes128gcm,aesgcm'],
        ], [
            'endpoint.required' => 'Некорректная подписка на уведомления.',
            'keys.p256dh.required' => 'Некорректная подписка на уведомления.',
            'keys.auth.required' => 'Некорректная подписка на уведомления.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        /** @var array{endpoint: string, keys: array{p256dh: string, auth: string}, content_encoding?: string} $validated */
        $validated = $validator->validated();

        return [
            'endpoint' => $validated['endpoint'],
            'keys' => $validated['keys'],
            'content_encoding' => $validated['content_encoding'] ?? 'aes128gcm',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{endpoint: string}
     */
    private function validateUnsubscribe(array $data): array
    {
        $validator = Validator::make($data, [
            'endpoint' => ['required', 'string', 'max:2048'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        /** @var array{endpoint: string} $validated */
        $validated = $validator->validated();

        return $validated;
    }
}
