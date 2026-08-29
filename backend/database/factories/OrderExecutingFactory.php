<?php

namespace Database\Factories;

use App\Enums\OrderExecutingStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderExecuting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderExecuting>
 */
class OrderExecutingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'executor_id' => User::factory()->state(['role' => UserRole::Executor]),
            'status' => OrderExecutingStatus::Wait,
            'process_at' => null,
            'complete_at' => null,
        ];
    }
}
