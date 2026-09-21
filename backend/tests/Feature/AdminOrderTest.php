<?php

namespace Tests\Feature;

use App\Enums\AdminPermission;
use App\Enums\AdminRole;
use App\Enums\OrderExecutingStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Admin;
use App\Models\File;
use App\Models\Order;
use App\Models\OrderExecuting;
use App\Models\OrderPoint;
use App\Models\OrderType;
use App\Models\User;
use App\Services\Centrifugo\CentrifugoClient;
use Database\Seeders\AdminPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminPermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_guest_cannot_list_admin_orders(): void
    {
        $this->getJson('/api/admin/orders')->assertUnauthorized();
    }

    public function test_pwa_user_cannot_access_admin_orders(): void
    {
        $user = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($user, 'api')
            ->getJson('/api/admin/orders')
            ->assertUnauthorized();
    }

    public function test_admin_can_login_and_see_me(): void
    {
        $admin = $this->makeAdmin();

        $login = $this->postJson('/api/admin/auth/login', [
            'login' => $admin->email,
            'password' => 'password',
        ]);
        $login->assertOk()
            ->assertJsonPath('admin.email', $admin->email)
            ->assertJsonPath('admin.roles.0', AdminRole::SuperAdmin->value);

        $token = $login->json('token');
        $this->assertNotEmpty($token);

        $this->withToken($token)
            ->getJson('/api/admin/auth/me')
            ->assertOk()
            ->assertJsonPath('id', $admin->id)
            ->assertJsonPath('email', $admin->email);
    }

    public function test_admin_lists_moderate_orders_by_default(): void
    {
        $admin = $this->makeAdmin();
        $author = User::factory()->create(['role' => UserRole::Customer]);
        $pending = $this->orderFor($author, OrderStatus::Moderate);
        $this->orderFor($author, OrderStatus::Wait);

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/orders')
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $pending->id);
    }

    public function test_admin_lists_orders_paginated_by_15(): void
    {
        $admin = $this->makeAdmin();
        $author = User::factory()->create(['role' => UserRole::Customer]);
        // 16 заказов в статусе Wait: первая страница — 15, вторая — 1.
        for ($i = 0; $i < 16; $i++) {
            $this->orderFor($author, OrderStatus::Wait);
        }

        $first = $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/orders?'.http_build_query(['status' => 'wait']))
            ->assertOk()
            ->assertJsonCount(15, 'items');

        $nextCursor = $first->json('next_cursor');
        $this->assertNotNull($nextCursor);

        $second = $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/orders?'.http_build_query(['status' => 'wait', 'cursor' => $nextCursor]))
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('next_cursor', null);

        $firstIds = collect($first->json('items'))->pluck('id');
        $secondIds = collect($second->json('items'))->pluck('id');
        $this->assertTrue($firstIds->merge($secondIds)->unique()->count() === 16);
    }

    public function test_admin_filters_orders_by_type_cost_and_number(): void
    {
        $admin = $this->makeAdmin();
        $author = User::factory()->create(['role' => UserRole::Customer]);

        $typeBuy = OrderType::query()->create(['name' => 'Купим и привезем', 'description' => 'Покупка и доставка']);
        $typeHelp = OrderType::query()->create(['name' => 'Помощь в переносе', 'description' => 'Грузчики']);

        $cheap = $this->orderFor($author, OrderStatus::Wait, ['order_type_id' => $typeBuy->id, 'cost' => 1000]);
        $pricey = $this->orderFor($author, OrderStatus::Wait, ['order_type_id' => $typeHelp->id, 'cost' => 5000]);

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/orders?'.http_build_query(['status' => 'wait', 'order_type_id' => $typeHelp->id]))
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $pricey->id);

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/orders?'.http_build_query(['status' => 'wait', 'cost_min' => 3000]))
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $pricey->id);

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/orders?'.http_build_query(['status' => 'wait', 'cost_max' => 2000]))
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $cheap->id);

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/orders?'.http_build_query(['status' => 'wait', 'cost_min' => 1000, 'cost_max' => 2000]))
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $cheap->id);

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/orders?'.http_build_query(['status' => 'wait', 'id' => $pricey->id]))
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $pricey->id);

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/orders?'.http_build_query(['status' => 'wait', 'order_type_id' => 999]))
            ->assertUnprocessable();

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/orders?'.http_build_query(['status' => 'wait', 'cost_min' => -5]))
            ->assertUnprocessable();
    }

    public function test_admin_approves_order_and_publishes_realtime(): void
    {
        $this->mock(CentrifugoClient::class, function ($mock) {
            $mock->shouldReceive('publish')->twice();
            $mock->shouldReceive('broadcast')->once();
        });

        $admin = $this->makeAdmin();
        $author = User::factory()->create(['role' => UserRole::Customer]);
        $order = $this->orderFor($author, OrderStatus::Moderate);

        $this->actingAs($admin, 'admin')
            ->postJson("/api/admin/orders/{$order->id}/approve")
            ->assertOk()
            ->assertJsonPath('order.status', 'wait');

        $this->assertSame(OrderStatus::Wait, $order->fresh()->status);
        $this->assertNull($order->fresh()->reason);
    }

    public function test_admin_cancels_order_with_reason(): void
    {
        $this->mock(CentrifugoClient::class, function ($mock) {
            $mock->shouldReceive('publish')->twice();
            $mock->shouldReceive('broadcast')->never();
        });

        $admin = $this->makeAdmin();
        $author = User::factory()->create(['role' => UserRole::Customer]);
        $order = $this->orderFor($author, OrderStatus::Moderate);

        $this->actingAs($admin, 'admin')
            ->postJson("/api/admin/orders/{$order->id}/cancel", [
                'reason' => 'Нарушение правил площадки.',
            ])
            ->assertOk()
            ->assertJsonPath('order.status', 'cancel')
            ->assertJsonPath('order.reason', 'Нарушение правил площадки.');
    }

    public function test_cannot_approve_order_that_is_not_on_moderation(): void
    {
        $this->mock(CentrifugoClient::class);
        $admin = $this->makeAdmin();
        $author = User::factory()->create(['role' => UserRole::Customer]);
        $order = $this->orderFor($author, OrderStatus::Wait);

        $this->actingAs($admin, 'admin')
            ->postJson("/api/admin/orders/{$order->id}/approve")
            ->assertUnprocessable();
    }

    public function test_admin_order_show_includes_point_files(): void
    {
        $admin = $this->makeAdmin();
        $author = User::factory()->create(['role' => UserRole::Customer]);
        $order = $this->orderFor($author, OrderStatus::Wait);
        $file = File::query()->create([
            'name' => 'brief.pdf',
            'extension' => 'pdf',
            'size' => 512,
            'path' => 'attachments/brief.pdf',
        ]);
        $file->forceFill([
            'temporary_at' => null,
            'order_point_id' => $order->points[0]->id,
        ])->save();

        $this->actingAs($admin, 'admin')
            ->getJson("/api/admin/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('order.points.0.files.0.name', 'brief.pdf')
            ->assertJsonPath('order.points.0.files.0.url', '/api/files/'.$file->id);
    }

    public function test_admin_order_show_includes_execution_complete_at(): void
    {
        $admin = $this->makeAdmin();
        $author = User::factory()->create(['role' => UserRole::Customer]);
        $order = $this->orderFor($author, OrderStatus::Complete);
        $completeAt = now()->subHour()->startOfSecond();
        OrderExecuting::factory()->create([
            'order_id' => $order->id,
            'status' => OrderExecutingStatus::Complete,
            'complete_at' => $completeAt,
        ]);

        $this->actingAs($admin, 'admin')
            ->getJson("/api/admin/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('order.complete_at', $completeAt->toISOString());
    }

    public function test_admin_without_approve_permission_is_forbidden(): void
    {
        $admin = Admin::factory()->create();
        $admin->givePermissionTo(AdminPermission::OrdersView);
        $author = User::factory()->create(['role' => UserRole::Customer]);
        $order = $this->orderFor($author, OrderStatus::Moderate);

        $this->actingAs($admin, 'admin')
            ->postJson("/api/admin/orders/{$order->id}/approve")
            ->assertForbidden();
    }

    private function makeAdmin(): Admin
    {
        $admin = Admin::factory()->create();
        $admin->assignRole(AdminRole::SuperAdmin);

        return $admin;
    }

    private function orderFor(User $author, OrderStatus $status, array $attributes = []): Order
    {
        $order = Order::factory()->for($author)->create([
            'description' => 'Документы',
            'status' => $status,
            ...$attributes,
        ]);
        OrderPoint::factory()->for($order)->create([
            'position' => 1,
            'lat' => 55.757,
            'lon' => 37.615,
        ]);

        return $order->load('points', 'user');
    }
}
