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
    public const PAGE_SIZE = 20;

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
            ->with(['points', 'user', 'currentExecuting'])
            ->orderByDesc('id');

        if ($payload['status'] !== null) {
            $query->where('status', $payload['status']);
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
     * @return array{status: OrderStatus|null, cursor: int|null}
     */
    private function validateList(array $data): array
    {
        $validator = Validator::make($data, [
            'status' => ['nullable', 'string', Rule::in([...array_map(
                fn (OrderStatus $status) => $status->value,
                OrderStatus::cases(),
            ), 'all'])],
            'cursor' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        /** @var array{status?: string|null, cursor?: int|null} $validated */
        $validated = $validator->validated();
        $statusValue = $validated['status'] ?? OrderStatus::Moderate->value;

        return [
            'status' => $statusValue === 'all' ? null : OrderStatus::from($statusValue),
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
