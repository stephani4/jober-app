<?php

namespace App\Http\Resources;

use App\Enums\OrderExecutingStatus;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('orderType');

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'order_type_id' => $this->order_type_id,
            'order_type' => $this->orderType
                ? OrderTypeResource::make($this->orderType)->resolve($request)
                : null,
            'description' => $this->description,
            'cost' => (float) $this->cost,
            'status' => $this->status->value,
            'reason' => $this->reason,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'role' => $this->user->role?->value,
            ]),
            // Исполнитель, принявший заказ в работу.
            'executor' => $this->whenLoaded('currentExecuting', fn () => $this->assignedExecutor($request)),
            'points' => $this->whenLoaded('points', fn () => $this->points->map(fn ($point) => [
                'id' => $point->id,
                'description' => $point->description,
                'address' => $point->address,
                'lat' => $point->lat !== null ? (float) $point->lat : null,
                'lon' => $point->lon !== null ? (float) $point->lon : null,
                'position' => (int) $point->position,
                'cost' => (float) $point->cost,
                'entrance' => $point->entrance !== null ? (int) $point->entrance : null,
                'floor' => $point->floor !== null ? (int) $point->floor : null,
                'apartment' => $point->apartment,
                'intercom' => $point->intercom !== null ? (int) $point->intercom : null,
            ])->values()->all()),
        ];
    }

    /**
     * Исполнитель текущего назначения: null, если заказ ещё не взят в работу.
     *
     * @return array<string, mixed>|null
     */
    private function assignedExecutor(Request $request): ?array
    {
        $executing = $this->currentExecuting;
        $executor = $executing?->executor;

        // Отменённое выполнение (отказ исполнителя или отмена заказа) исполнителя не показывает.
        if (! $executor || $executing->status === OrderExecutingStatus::Cancel) {
            return null;
        }

        return OrderExecutorResource::make($executor)->resolve($request);
    }
}
