<?php

namespace App\Http\Resources;

use App\Models\OrderExecuting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Назначение исполнителя и статусы точек выполнения.
 *
 * @mixin OrderExecuting
 */
class OrderExecutingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'executor_id' => $this->executor_id,
            'status' => $this->status->value,
            'process_at' => $this->process_at?->toISOString(),
            'complete_at' => $this->complete_at?->toISOString(),
            'lat' => $this->lat !== null ? (float) $this->lat : null,
            'lon' => $this->lon !== null ? (float) $this->lon : null,
            'location_at' => $this->location_at?->toISOString(),
            'order' => $this->whenLoaded('order', fn () => OrderResource::make($this->order)->resolve($request)),
            'points' => $this->whenLoaded('points', fn () => $this->points
                ->sortBy(fn ($point) => $point->orderPoint?->position ?? 0)
                ->values()
                ->map(fn ($point) => [
                    'id' => $point->id,
                    'order_executing_id' => $point->order_executing_id,
                    'order_point_id' => $point->order_point_id,
                    'status' => $point->status->value,
                    'process_at' => $point->process_at?->toISOString(),
                    'complete_at' => $point->complete_at?->toISOString(),
                    'order_point' => $point->relationLoaded('orderPoint') && $point->orderPoint
                        ? [
                            'id' => $point->orderPoint->id,
                            'description' => $point->orderPoint->description,
                            'address' => $point->orderPoint->address,
                            'lat' => $point->orderPoint->lat !== null ? (float) $point->orderPoint->lat : null,
                            'lon' => $point->orderPoint->lon !== null ? (float) $point->orderPoint->lon : null,
                            'position' => (int) $point->orderPoint->position,
                            'cost' => (float) $point->orderPoint->cost,
                        ]
                        : null,
                ])
                ->all()),
        ];
    }
}
