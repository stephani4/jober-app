<?php

namespace Tests\Feature;

use App\Models\OrderType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_order_types(): void
    {
        $this->getJson('/api/order-types')->assertUnauthorized();
    }

    public function test_user_can_list_seeded_order_types(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/order-types')
            ->assertOk()
            ->assertJsonCount(3, 'types');

        $response->assertJsonPath('types.0.id', OrderType::BUY_AND_DELIVER);
        $response->assertJsonPath('types.0.name', 'Купим и привезем');
        $response->assertJsonPath('types.0.max_points', 1);
        $response->assertJsonPath('types.1.id', OrderType::HELP_CARRY);
        $response->assertJsonPath('types.1.max_points', 20);
        $response->assertJsonPath('types.2.id', OrderType::ERRAND);
        $response->assertJsonPath('types.2.name', 'Поручение');
    }
}
