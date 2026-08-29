<?php

namespace Database\Factories;

use App\Enums\OrderExecutingStatus;
use App\Models\OrderExecuting;
use App\Models\OrderExecutingPoint;
use App\Models\OrderPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderExecutingPoint>
 */
class OrderExecutingPointFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_executing_id' => OrderExecuting::factory(),
            'order_point_id' => OrderPoint::factory(),
            'status' => OrderExecutingStatus::Wait,
            'process_at' => null,
            'complete_at' => null,
        ];
    }
}
