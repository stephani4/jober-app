<?php

namespace Database\Factories;

use App\Models\OrderExecuting;
use App\Models\OrderExecutingRating;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderExecutingRating>
 */
class OrderExecutingRatingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_executing_id' => OrderExecuting::factory(),
            'rating' => fake()->numberBetween(1, 5),
        ];
    }
}
