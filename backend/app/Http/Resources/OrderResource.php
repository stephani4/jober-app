<?php

namespace App\Http\Resources;

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
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
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
            'points' => $this->whenLoaded('points', fn () => $this->points->map(fn ($point) => [
                'id' => $point->id,
                'description' => $point->description,
                'address' => $point->address,
                'lat' => $point->lat !== null ? (float) $point->lat : null,
                'lon' => $point->lon !== null ? (float) $point->lon : null,
                'position' => (int) $point->position,
                'cost' => (float) $point->cost,
            ])->values()->all()),
        ];
    }
}
