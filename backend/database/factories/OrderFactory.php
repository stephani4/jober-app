<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_type_id' => OrderType::ERRAND,
            'description' => fake()->paragraph(),
            'cost' => fake()->randomFloat(2, 500, 8000),
            'status' => OrderStatus::Wait,
            'reason' => null,
        ];
    }
}
