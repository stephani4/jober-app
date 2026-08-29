<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Создание и выборка заказов.
 */
class OrderService
{
    public const HISTORY_PAGE_SIZE = 15;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Order
    {
        $payload = $this->validateCreate($data);

        return DB::transaction(function () use ($user, $payload) {
            $order = Order::query()->create([
                'user_id' => $user->id,
                'description' => $payload['description'] ?? '',
                'cost' => $payload['cost'],
                'status' => OrderStatus::Moderate,
                'reason' => null,
            ]);

            foreach (array_values($payload['points']) as $index => $point) {
                $order->points()->create([
                    'description' => $point['description'],
                    'address' => $point['address'] ?? null,
                    'lat' => $point['lat'],
                    'lon' => $point['lon'],
                    'position' => $index + 1,
                    'cost' => 0,
                ]);
            }

            return $order->load(['points', 'user', 'currentExecuting']);
        });
    }

    /**
     * Заказы автора: на модерации, в ожидании и в работе.
     *
     * @return Collection<int, Order>
     */
    public function listMine(User $user): Collection
    {
        return Order::query()
            ->with(['points', 'user', 'currentExecuting'])
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
            ->with(['points', 'user', 'currentExecuting'])
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
            ->with(['points', 'user', 'currentExecuting'])
            ->where('status', OrderStatus::Wait)
            ->latest()
            ->limit(50)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{description?: string, cost: float|int|string, points: list<array<string, mixed>>}
     */
    private function validateCreate(array $data): array
    {
        $validator = Validator::make($data, [
            'description' => ['nullable', 'string', 'max:5000'],
            'cost' => ['required', 'numeric', 'min:0.01'],
            'points' => ['required', 'array', 'min:1', 'max:20'],
            'points.*.description' => ['required', 'string', 'max:2000'],
            'points.*.address' => ['nullable', 'string', 'max:500'],
            'points.*.lat' => ['required', 'numeric', 'between:-90,90'],
            'points.*.lon' => ['required', 'numeric', 'between:-180,180'],
        ], [
            'cost.min' => 'Укажите стоимость заказа.',
            'points.min' => 'Добавьте хотя бы одну точку.',
            'points.*.description.required' => 'Опишите, что нужно сделать в точке.',
            'points.*.lat.required' => 'Выберите точку на карте.',
            'points.*.lon.required' => 'Выберите точку на карте.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        /** @var array{description?: string, cost: float|int|string, points: list<array<string, mixed>>} $validated */
        $validated = $validator->validated();

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
