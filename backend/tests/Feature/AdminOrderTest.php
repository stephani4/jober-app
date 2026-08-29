<?php

namespace Tests\Feature;

use App\Enums\AdminPermission;
use App\Enums\AdminRole;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderPoint;
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

    private function orderFor(User $author, OrderStatus $status): Order
    {
        $order = Order::factory()->for($author)->create([
            'description' => 'Документы',
            'status' => $status,
        ]);
        OrderPoint::factory()->for($order)->create([
            'position' => 1,
            'lat' => 55.757,
            'lon' => 37.615,
        ]);

        return $order->load('points', 'user');
    }
}
