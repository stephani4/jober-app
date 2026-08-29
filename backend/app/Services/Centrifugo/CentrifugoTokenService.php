<?php

namespace App\Services\Centrifugo;

use App\Models\User;

/**
 * Выпускает connection JWT для клиента Centrifugo.
 */
class CentrifugoTokenService
{
    /**
     * JWT с server-side подписками: личный канал и лента заказов.
     */
    public function issue(User $user): string
    {
        $now = time();
        $ttl = (int) config('centrifugo.token_ttl', 3600);

        return $this->encode([
            'sub' => (string) $user->id,
            'iat' => $now,
            'exp' => $now + $ttl,
            'channels' => [
                $this->personalChannel($user),
                (string) config('centrifugo.channels.search'),
            ],
        ]);
    }

    public function personalChannel(User $user): string
    {
        return (string) config('centrifugo.channels.personal_prefix').$user->id;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function encode(array $payload): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $body = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $secret = (string) config('centrifugo.token_hmac_secret');
        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', $header.'.'.$body, $secret, true),
        );

        return $header.'.'.$body.'.'.$signature;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
