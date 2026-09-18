<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Обратное геокодирование VK Maps: адрес по координатам точки.
 *
 * Отдельного reverse-эндпоинта у VK Maps нет: обратное геокодирование — это поиск
 * (`/search`) со строкой `q=lat,lon`, возвращающий ближайшие объекты с готовым адресом
 * (`address_details`) и собственными координатами (`pin`).
 */
class VkMapsGeocodingService
{
    /** Радиус, в котором найденный адрес считаем адресом самой точки (метры). */
    private const MATCH_RADIUS_METERS = 150;

    private const EARTH_RADIUS_METERS = 6371000;

    private const REQUEST_TIMEOUT_SECONDS = 5;

    /** Сколько ближайших объектов запрашиваем. */
    private const RESULT_LIMIT = 10;

    /**
     * Адрес по координатам выбранной точки.
     *
     * Возвращаем `null`, если рядом адреса нет: координаты вместо адреса не подставляем.
     */
    public function reverseGeocode(float $lat, float $lon): ?string
    {
        $results = $this->search($lat, $lon);
        if ($results === null) {
            return null;
        }

        $match = $this->nearestMatch($results, $lat, $lon);

        return $match === null ? null : $this->formatAddress($match);
    }

    /**
     * Запрос к `/search` со строкой `lat,lon`; `null` при сетевой ошибке или ошибке HTTP.
     *
     * @return list<array<string, mixed>>|null
     */
    private function search(float $lat, float $lon): ?array
    {
        try {
            // verify управляется конфигом: локально без CA-бандла можно отключить (VK_MAPS_HTTP_VERIFY=false).
            $response = Http::withOptions([
                'verify' => (bool) config('services.vk_maps.verify', true),
            ])
                ->timeout(self::REQUEST_TIMEOUT_SECONDS)
                ->get($this->baseUrl().'/search', [
                    'api_key' => config('services.vk_maps.key'),
                    'q' => sprintf('%.6f,%.6f', $lat, $lon),
                    'lang' => 'ru',
                    'limit' => self::RESULT_LIMIT,
                    'fields' => 'name,address,type,pin,address_details',
                ]);
        } catch (Throwable $exception) {
            Log::warning('VK Maps reverse geocoding failed', [
                'lat' => $lat,
                'lon' => $lon,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        if ($response->failed()) {
            Log::warning('VK Maps reverse geocoding failed', [
                'lat' => $lat,
                'lon' => $lon,
                'status' => $response->status(),
            ]);

            return null;
        }

        /** @var list<array<string, mixed>> $results */
        $results = $response->json('results', []);

        return $results;
    }

    /**
     * Ближайший к точке объект с адресом; здания (дома с номером) предпочтительнее.
     *
     * @param  list<array<string, mixed>>  $results
     * @return array<string, mixed>|null
     */
    private function nearestMatch(array $results, float $lat, float $lon): ?array
    {
        $candidates = [];

        foreach ($results as $item) {
            $pin = $item['pin'] ?? null;
            $address = trim((string) ($item['address'] ?? ''));

            if ($address === '' || ! is_array($pin) || count($pin) < 2 || ! is_numeric($pin[0]) || ! is_numeric($pin[1])) {
                continue;
            }

            $distance = $this->distanceMeters($lat, $lon, (float) $pin[1], (float) $pin[0]);
            if ($distance > self::MATCH_RADIUS_METERS) {
                continue;
            }

            $candidates[] = [
                'item' => $item,
                'distance' => $distance,
                // Здания приоритетнее прочих типов (улицы, районы и т.п.).
                'is_building' => ($item['type'] ?? null) === 'building' ? 0 : 1,
            ];
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, static function (array $a, array $b): int {
            return [$a['is_building'], $a['distance']] <=> [$b['is_building'], $b['distance']];
        });

        /** @var array<string, mixed> */
        return $candidates[0]['item'];
    }

    /**
     * Компактный адрес «город, улица, дом»: полная строка API дублирует регион и район.
     *
     * @param  array<string, mixed>  $item
     */
    private function formatAddress(array $item): string
    {
        $details = is_array($item['address_details'] ?? null) ? $item['address_details'] : [];
        $parts = [];

        foreach (['locality', 'street', 'building'] as $key) {
            $part = trim((string) ($details[$key] ?? ''));
            if ($part !== '') {
                $parts[] = $part;
            }
        }

        if ($parts !== []) {
            return implode(', ', $parts);
        }

        return trim((string) ($item['address'] ?? ''));
    }

    /** Расстояние между координатами по формуле гаверсинуса, метры. */
    private function distanceMeters(float $latA, float $lonA, float $latB, float $lonB): float
    {
        $toRadians = static fn (float $degrees): float => $degrees * M_PI / 180;
        $deltaLat = $toRadians($latB - $latA);
        $deltaLon = $toRadians($lonB - $lonA);
        $a = sin($deltaLat / 2) ** 2
            + cos($toRadians($latA)) * cos($toRadians($latB)) * sin($deltaLon / 2) ** 2;

        return 2 * self::EARTH_RADIUS_METERS * asin(min(1.0, sqrt($a)));
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.vk_maps.base_url', 'https://maps.vk.com/api'), '/');
    }
}
