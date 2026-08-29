<?php

namespace App\Services\Centrifugo;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * HTTP API-клиент Centrifugo: публикация событий в каналы.
 */
class CentrifugoClient
{
    /**
     * Публикует данные в один канал.
     *
     * @param  array<string, mixed>  $data
     */
    public function publish(string $channel, array $data): void
    {
        $this->post('/api/publish', [
            'channel' => $channel,
            'data' => $data,
        ]);
    }

    /**
     * Публикует одни и те же данные сразу в несколько каналов.
     *
     * @param  list<string>  $channels
     * @param  array<string, mixed>  $data
     */
    public function broadcast(array $channels, array $data): void
    {
        $this->post('/api/broadcast', [
            'channels' => $channels,
            'data' => $data,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function post(string $path, array $payload): void
    {
        $url = rtrim((string) config('centrifugo.url'), '/').$path;

        $response = Http::timeout(5)
            ->withHeaders([
                'X-API-Key' => (string) config('centrifugo.api_key'),
            ])
            ->acceptJson()
            ->post($url, $payload);

        if ($response->failed()) {
            throw new RuntimeException('Centrifugo API request failed: '.$response->body());
        }

        $error = $response->json('error');
        if (is_array($error)) {
            throw new RuntimeException('Centrifugo API error: '.json_encode($error));
        }
    }
}
