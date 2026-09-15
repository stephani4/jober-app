<?php

namespace App\Services\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderModerationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Списки и ручная модерация заказов для админки.
 */
class AdminOrderService
{
    public const PAGE_SIZE = 15;

    public function __construct(
        private readonly OrderModerationService $moderation,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{items: Collection<int, Order>, next_cursor: int|null}
     */
    public function list(array $data): array
    {
        $payload = $this->validateList($data);
        $limit = self::PAGE_SIZE;

        $query = Order::query()
            ->with(['points', 'user', 'currentExecuting', 'orderType'])
            ->orderByDesc('id');

        if ($payload['status'] !== null) {
            $query->where('status', $payload['status']);
        }

        if ($payload['id'] !== null) {
            $query->where('id', $payload['id']);
        }

        if ($payload['order_type_id'] !== null) {
            $query->where('order_type_id', $payload['order_type_id']);
        }

        if ($payload['cost_min'] !== null) {
            $query->where('cost', '>=', $payload['cost_min']);
        }

        if ($payload['cost_max'] !== null) {
            $query->where('cost', '<=', $payload['cost_max']);
        }

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

    public function approve(Order $order): Order
    {
        $order->loadMissing(['points', 'user', 'currentExecuting']);

        return $this->moderation->approve($order);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function cancel(Order $order, array $data): Order
    {
        $payload = $this->validateCancel($data);
        $order->loadMissing(['points', 'user', 'currentExecuting']);

        return $this->moderation->reject($order, $payload['reason']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{id: int|null, status: OrderStatus|null, order_type_id: int|null, cost_min: float|null, cost_max: float|null, cursor: int|null}
     */
    private function validateList(array $data): array
    {
        $validator = Validator::make($data, [
            'status' => ['nullable', 'string', Rule::in([...array_map(
                fn (OrderStatus $status) => $status->value,
                OrderStatus::cases(),
            ), 'all'])],
            'id' => ['nullable', 'integer', 'min:1'],
            'order_type_id' => ['nullable', 'integer', Rule::exists('order_types', 'id')],
            'cost_min' => ['nullable', 'numeric', 'min:0'],
            'cost_max' => ['nullable', 'numeric', 'min:0'],
            'cursor' => ['nullable', 'integer', 'min:1'],
        ], [
            'order_type_id.exists' => 'Выберите корректный вид заказа.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        /** @var array{status?: string|null, id?: int|null, order_type_id?: int|null, cost_min?: string|null, cost_max?: string|null, cursor?: int|null} $validated */
        $validated = $validator->validated();
        $statusValue = $validated['status'] ?? OrderStatus::Moderate->value;

        return [
            'id' => $validated['id'] ?? null,
            'status' => $statusValue === 'all' ? null : OrderStatus::from($statusValue),
            'order_type_id' => $validated['order_type_id'] ?? null,
            'cost_min' => isset($validated['cost_min']) ? (float) $validated['cost_min'] : null,
            'cost_max' => isset($validated['cost_max']) ? (float) $validated['cost_max'] : null,
            'cursor' => $validated['cursor'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{reason: string}
     */
    private function validateCancel(array $data): array
    {
        $validator = Validator::make($data, [
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ], [
            'reason.required' => 'Укажите причину отказа.',
            'reason.min' => 'Укажите причину отказа.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        /** @var array{reason: string} $validated */
        $validated = $validator->validated();

        return [
            'reason' => trim($validated['reason']),
        ];
    }
}
