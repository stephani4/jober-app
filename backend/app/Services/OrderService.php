<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderType;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Создание и выборка заказов.
 */
class OrderService
{
    public const HISTORY_PAGE_SIZE = 15;

    /** Шаблон «широта, долгота»: так выглядел адрес, сохранённый без обратного геокодирования. */
    private const COORDS_PATTERN = '/^-?\d+(?:\.\d+)?\s*,\s*-?\d+(?:\.\d+)?$/';

    public function __construct(
        private readonly VkMapsGeocodingService $geocoder,
        private readonly FileService $files,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Order
    {
        $payload = $this->validateCreate($data);
        $points = $this->resolvePointAddresses($payload['points']);

        return DB::transaction(function () use ($user, $payload, $points) {
            $order = Order::query()->create([
                'user_id' => $user->id,
                'order_type_id' => $payload['order_type_id'],
                'description' => $payload['description'] ?? '',
                'cost' => $payload['cost'],
                'status' => OrderStatus::Moderate,
                'reason' => null,
            ]);

            foreach (array_values($points) as $index => $point) {
                $created = $order->points()->create([
                    'description' => $point['description'],
                    'address' => $point['address'] ?? null,
                    'lat' => $point['lat'],
                    'lon' => $point['lon'],
                    'position' => $index + 1,
                    'cost' => 0,
                    'entrance' => $point['entrance'] ?? null,
                    'floor' => $point['floor'] ?? null,
                    'apartment' => $point['apartment'] ?? null,
                    'intercom' => $point['intercom'] ?? null,
                ]);

                $this->files->commit(
                    array_map('intval', $point['file_ids'] ?? []),
                    $created->id,
                );
            }

            return $order->load(['points.files', 'user', 'currentExecuting', 'orderType']);
        });
    }

    /**
     * Точки без адреса (или с координатами вместо адреса) дополняем адресом через
     * обратное геокодирование VK Maps. Если адрес найти не удалось — оставляем null:
     * координаты в поле address не сохраняем.
     *
     * @param  list<array<string, mixed>>  $points
     * @return list<array<string, mixed>>
     */
    private function resolvePointAddresses(array $points): array
    {
        foreach ($points as $index => $point) {
            $address = trim((string) ($point['address'] ?? ''));
            if ($address !== '' && preg_match(self::COORDS_PATTERN, $address) !== 1) {
                continue;
            }

            $resolved = $this->geocoder->reverseGeocode((float) $point['lat'], (float) $point['lon']);
            $points[$index]['address'] = $resolved !== null ? mb_substr($resolved, 0, 500) : null;
        }

        return $points;
    }

    /**
     * Заказы автора: на модерации, в ожидании и в работе.
     *
     * @return Collection<int, Order>
     */
    public function listMine(User $user): Collection
    {
        return Order::query()
            ->with(['points.files', 'user', 'currentExecuting.executor.avatar', 'currentExecuting.rating', 'orderType'])
            ->where('user_id', $user->id)
            ->whereIn('status', [
                OrderStatus::Moderate,
                OrderStatus::Wait,
                OrderStatus::Process,
            ])
            ->latest()
            ->get();
    }

    /**
     * Завершённые и отклонённые заказы автора, страницами по HISTORY_PAGE_SIZE.
     *
     * @param  array<string, mixed>  $data
     * @return array{items: Collection<int, Order>, next_cursor: int|null}
     */
    public function listHistory(User $user, array $data): array
    {
        $payload = $this->validateHistory($data);
        $limit = self::HISTORY_PAGE_SIZE;

        $query = Order::query()
            ->with(['points.files', 'user', 'currentExecuting.executor.avatar', 'currentExecuting.rating', 'orderType'])
            ->where('user_id', $user->id)
            ->whereIn('status', [
                OrderStatus::Complete,
                OrderStatus::Cancel,
            ])
            ->orderByDesc('id');

        if ($payload['cursor'] !== null) {
            $query->where('id', '<', $payload['cursor']);
        }

        $rows = $query->limit($limit + 1)->get();
        $hasMore = $rows->count() > $limit;
        $page = $hasMore ? $rows->take($limit)->values() : $rows->values();

        return [
            'items' => $page,
            'next_cursor' => $hasMore ? (int) $page->last()->id : null,
        ];
    }

    /**
     * Лента открытых заказов для поиска: прошедшие модерацию, без исполнителя.
     *
     * @return Collection<int, Order>
     */
    public function listFeed(): Collection
    {
        return Order::query()
            ->with(['points.files', 'user', 'currentExecuting.executor.avatar', 'currentExecuting.rating', 'orderType'])
            ->where('status', OrderStatus::Wait)
            ->latest()
            ->limit(50)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{order_type_id: int, description?: string, cost: float|int|string, points: list<array<string, mixed>>}
     */
    private function validateCreate(array $data): array
    {
        $typeId = (int) ($data['order_type_id'] ?? 0);
        $isBuyAndDeliver = $typeId === OrderType::BUY_AND_DELIVER;
        $pointsMax = $isBuyAndDeliver ? 1 : OrderType::DEFAULT_MAX_POINTS;

        $validator = Validator::make($data, [
            'order_type_id' => ['required', 'integer', Rule::exists('order_types', 'id')],
            'description' => ['nullable', 'string', 'max:5000'],
            'cost' => ['required', 'numeric', 'min:0.01'],
            'points' => ['required', 'array', 'min:1', 'max:'.$pointsMax],
            'points.*.description' => ['required', 'string', 'max:2000'],
            'points.*.address' => ['nullable', 'string', 'max:500'],
            'points.*.lat' => ['required', 'numeric', 'between:-90,90'],
            'points.*.lon' => ['required', 'numeric', 'between:-180,180'],
            // Данные доступа в здание заполняются только для выбранного здания и не обязательны.
            'points.*.entrance' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'points.*.floor' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'points.*.apartment' => ['nullable', 'string', 'max:20'],
            'points.*.intercom' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'points.*.file_ids' => ['nullable', 'array', 'max:10'],
            'points.*.file_ids.*' => [
                'integer',
                Rule::exists('files', 'id')->where(function ($query) {
                    $query->whereNotNull('temporary_at')->whereNull('order_point_id');
                }),
            ],
        ], [
            'order_type_id.required' => 'Выберите вид заказа.',
            'order_type_id.exists' => 'Выберите вид заказа.',
            'cost.min' => 'Укажите стоимость заказа.',
            'points.min' => 'Добавьте хотя бы одну точку.',
            'points.max' => $isBuyAndDeliver
                ? 'Для этого вида заказа нужна одна точка доставки.'
                : 'Слишком много точек маршрута.',
            'points.*.description.required' => $isBuyAndDeliver
                ? 'Опишите, что нужно купить.'
                : 'Опишите, что нужно сделать в точке.',
            'points.*.lat.required' => 'Выберите точку на карте.',
            'points.*.lon.required' => 'Выберите точку на карте.',
            'points.*.file_ids.*.exists' => 'Файл не найден или уже прикреплён к заказу.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        /** @var array{order_type_id: int, description?: string, cost: float|int|string, points: list<array<string, mixed>>} $validated */
        $validated = $validator->validated();

        $fileIds = collect($validated['points'])
            ->pluck('file_ids')
            ->flatten()
            ->filter()
            ->values();

        if ($fileIds->count() !== $fileIds->unique()->count()) {
            throw ValidationException::withMessages([
                'points' => ['Один файл нельзя прикрепить к двум точкам.'],
            ]);
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{cursor: int|null}
     */
    private function validateHistory(array $data): array
    {
        $validator = Validator::make($data, [
            'cursor' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        /** @var array{cursor?: int|null} $validated */
        $validated = $validator->validated();

        return [
            'cursor' => $validated['cursor'] ?? null,
        ];
    }
}
