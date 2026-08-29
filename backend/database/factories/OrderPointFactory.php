<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderPoint>
 */
class OrderPointFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'description' => fake()->sentence(),
            'address' => fake()->address(),
            'lat' => fake()->latitude(),
            'lon' => fake()->longitude(),
            'position' => 1,
            'cost' => fake()->randomFloat(2, 100, 5000),
        ];
    }
}
