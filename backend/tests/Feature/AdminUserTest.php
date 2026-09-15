<?php

namespace Tests\Feature;

use App\Enums\AdminPermission;
use App\Enums\AdminRole;
use App\Enums\UserRole;
use App\Models\Admin;
use App\Models\User;
use Database\Seeders\AdminPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminPermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_guest_cannot_list_users(): void
    {
        $this->getJson('/api/admin/users')->assertUnauthorized();
    }

    public function test_pwa_user_cannot_access_admin_users(): void
    {
        $user = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($user, 'api')
            ->getJson('/api/admin/users')
            ->assertUnauthorized();
    }

    public function test_admin_lists_users_paginated_by_15(): void
    {
        $admin = $this->makeAdmin();
        User::factory()->count(17)->create(['role' => UserRole::Customer]);

        $first = $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonCount(15, 'items');

        $nextCursor = $first->json('next_cursor');
        $this->assertNotNull($nextCursor);

        $second = $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/users?'.http_build_query(['cursor' => $nextCursor]))
            ->assertOk()
            ->assertJsonCount(2, 'items')
            ->assertJsonPath('next_cursor', null);

        // Страницы не пересекаются и идут по убыванию id.
        $firstIds = collect($first->json('items'))->pluck('id');
        $secondIds = collect($second->json('items'))->pluck('id');
        $this->assertTrue($firstIds->merge($secondIds)->unique()->count() === 17);
        $this->assertTrue($firstIds->zip($secondIds)->every(
            fn ($pair) => $pair[0] > $pair[1],
        ));
    }

    public function test_admin_filters_users_by_name_email_birth_date_and_role(): void
    {
        $admin = $this->makeAdmin();
        $anna = User::factory()->create([
            'name' => 'Анна Смирнова',
            'email' => 'anna.smirnova@example.com',
            'birth_date' => '1992-05-14',
            'role' => UserRole::Executor,
        ]);
        User::factory()->create([
            'name' => 'Анна Козлова',
            'email' => 'anna.kozlova@example.com',
            'birth_date' => '1988-01-01',
            'role' => UserRole::Customer,
        ]);
        User::factory()->create([
            'name' => 'Иван Петров',
            'email' => 'ivan@example.com',
            'birth_date' => '1992-05-14',
            'role' => UserRole::Executor,
        ]);

        // SQLite (тестовая БД) не приводит кириллицу к нижнему регистру в LOWER(),
        // поэтому в тестах фильтруем подстроками без разницы регистров (в PG регистронезависимо).
        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/users?'.http_build_query(['name' => 'мирнова']))
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $anna->id)
            ->assertJsonPath('items.0.role', UserRole::Executor->value)
            ->assertJsonPath('items.0.role_label', 'Исполнитель')
            ->assertJsonPath('items.0.birth_date', '1992-05-14');

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/users?'.http_build_query(['email' => 'KOZLOVA']))
            ->assertOk()
            ->assertJsonCount(1, 'items');

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/users?'.http_build_query(['birth_date' => '1992-05-14']))
            ->assertOk()
            ->assertJsonCount(2, 'items');

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/users?'.http_build_query(['role' => 'customer']))
            ->assertOk()
            ->assertJsonCount(1, 'items');

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/users?'.http_build_query(['name' => 'нна', 'role' => 'executor']))
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $anna->id);
    }

    public function test_users_list_validates_filters(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/users?'.http_build_query(['role' => 'super-admin']))
            ->assertUnprocessable();

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/users?'.http_build_query(['birth_date' => '14.05.1992']))
            ->assertUnprocessable();
    }

    public function test_admin_without_users_permission_is_forbidden(): void
    {
        $admin = Admin::factory()->create();
        $admin->givePermissionTo(AdminPermission::OrdersView);

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/users')
            ->assertForbidden();
    }

    private function makeAdmin(): Admin
    {
        $admin = Admin::factory()->create();
        $admin->assignRole(AdminRole::SuperAdmin);

        return $admin;
    }
}
