<?php

namespace Tests\Feature;

use App\Enums\AdminPermission;
use App\Enums\AdminRole;
use App\Models\Admin;
use App\Models\User;
use Database\Seeders\AdminPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminAdministratorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminPermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_guest_cannot_list_admins(): void
    {
        $this->getJson('/api/admin/admins')->assertUnauthorized();
    }

    public function test_pwa_user_cannot_access_admins(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->getJson('/api/admin/admins')
            ->assertUnauthorized();
    }

    public function test_moderator_cannot_manage_admins(): void
    {
        $moderator = $this->makeAdmin(AdminRole::Moderator);

        $this->actingAs($moderator, 'admin')
            ->getJson('/api/admin/admins')
            ->assertForbidden();
    }

    public function test_admin_lists_staff_paginated_by_15(): void
    {
        $actor = $this->makeAdmin();
        Admin::factory()->count(16)->create()->each(
            fn (Admin $admin) => $admin->assignRole(AdminRole::Moderator),
        );

        $first = $this->actingAs($actor, 'admin')
            ->getJson('/api/admin/admins')
            ->assertOk()
            ->assertJsonCount(15, 'items');

        $nextCursor = $first->json('next_cursor');
        $this->assertNotNull($nextCursor);

        $this->actingAs($actor, 'admin')
            ->getJson('/api/admin/admins?'.http_build_query(['cursor' => $nextCursor]))
            ->assertOk()
            ->assertJsonCount(2, 'items')
            ->assertJsonPath('next_cursor', null);
    }

    public function test_admin_filters_staff_by_name_email_and_role(): void
    {
        $actor = $this->makeAdmin();
        $anna = Admin::factory()->create([
            'name' => 'Анна Смирнова',
            'email' => 'anna.smirnova@example.com',
        ]);
        $anna->assignRole(AdminRole::Moderator);

        $ivan = Admin::factory()->create([
            'name' => 'Иван Петров',
            'email' => 'ivan@example.com',
        ]);
        $ivan->assignRole(AdminRole::SuperAdmin);

        $this->actingAs($actor, 'admin')
            ->getJson('/api/admin/admins?'.http_build_query(['name' => 'мирнова']))
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $anna->id)
            ->assertJsonPath('items.0.roles.0', AdminRole::Moderator->value);

        $this->actingAs($actor, 'admin')
            ->getJson('/api/admin/admins?'.http_build_query(['email' => 'IVAN']))
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $ivan->id);

        $this->actingAs($actor, 'admin')
            ->getJson('/api/admin/admins?'.http_build_query(['role' => AdminRole::Moderator->value]))
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $anna->id);
    }

    public function test_admins_list_validates_role_filter(): void
    {
        $actor = $this->makeAdmin();

        $this->actingAs($actor, 'admin')
            ->getJson('/api/admin/admins?'.http_build_query(['role' => 'customer']))
            ->assertUnprocessable();
    }

    public function test_admin_creates_staff(): void
    {
        $actor = $this->makeAdmin();

        $response = $this->actingAs($actor, 'admin')
            ->postJson('/api/admin/admins', [
                'name' => 'Новый модератор',
                'email' => 'mod@example.com',
                'password' => 'secret123',
                'roles' => [AdminRole::Moderator->value],
            ])
            ->assertCreated()
            ->assertJsonPath('email', 'mod@example.com')
            ->assertJsonPath('roles.0', AdminRole::Moderator->value)
            ->assertJsonMissingPath('password');

        $this->assertDatabaseHas('admins', ['email' => 'mod@example.com']);
        $created = Admin::query()->where('email', 'mod@example.com')->first();
        $this->assertTrue(Hash::check('secret123', $created->password));
        $this->assertTrue($created->hasRole(AdminRole::Moderator));
        $this->assertArrayNotHasKey('password', $response->json());
    }

    public function test_create_validates_unique_email_and_password(): void
    {
        $actor = $this->makeAdmin();

        $this->actingAs($actor, 'admin')
            ->postJson('/api/admin/admins', [
                'name' => 'Дубль',
                'email' => $actor->email,
                'password' => 'secret123',
                'roles' => [AdminRole::Moderator->value],
            ])
            ->assertUnprocessable();

        $this->actingAs($actor, 'admin')
            ->postJson('/api/admin/admins', [
                'name' => 'Короткий пароль',
                'email' => 'short@example.com',
                'password' => 'short',
                'roles' => [AdminRole::Moderator->value],
            ])
            ->assertUnprocessable();
    }

    public function test_admin_shows_and_updates_staff(): void
    {
        $actor = $this->makeAdmin();
        $target = Admin::factory()->create([
            'name' => 'Старое имя',
            'email' => 'old@example.com',
        ]);
        $target->assignRole(AdminRole::Moderator);

        $this->actingAs($actor, 'admin')
            ->getJson('/api/admin/admins/'.$target->id)
            ->assertOk()
            ->assertJsonPath('id', $target->id)
            ->assertJsonPath('email', 'old@example.com');

        $this->actingAs($actor, 'admin')
            ->putJson('/api/admin/admins/'.$target->id, [
                'name' => 'Новое имя',
                'email' => 'new@example.com',
                'roles' => [AdminRole::Moderator->value],
            ])
            ->assertOk()
            ->assertJsonPath('name', 'Новое имя')
            ->assertJsonPath('email', 'new@example.com');

        $this->assertTrue(Hash::check('password', $target->fresh()->password));
    }

    public function test_update_changes_password_when_provided(): void
    {
        $actor = $this->makeAdmin();
        $target = Admin::factory()->create();
        $target->assignRole(AdminRole::Moderator);

        $this->actingAs($actor, 'admin')
            ->putJson('/api/admin/admins/'.$target->id, [
                'name' => $target->name,
                'email' => $target->email,
                'password' => 'newpass12',
                'roles' => [AdminRole::Moderator->value],
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('newpass12', $target->fresh()->password));
    }

    public function test_cannot_remove_last_super_admin(): void
    {
        $actor = $this->makeAdmin();

        $this->actingAs($actor, 'admin')
            ->putJson('/api/admin/admins/'.$actor->id, [
                'name' => $actor->name,
                'email' => $actor->email,
                'roles' => [AdminRole::Moderator->value],
            ])
            ->assertUnprocessable();

        $this->actingAs($actor, 'admin')
            ->deleteJson('/api/admin/admins/'.$actor->id)
            ->assertUnprocessable();
    }

    public function test_admin_cannot_delete_self(): void
    {
        $actor = $this->makeAdmin();
        $other = $this->makeAdmin();

        $this->actingAs($other, 'admin')
            ->deleteJson('/api/admin/admins/'.$other->id)
            ->assertUnprocessable();

        $this->actingAs($other, 'admin')
            ->deleteJson('/api/admin/admins/'.$actor->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('admins', ['id' => $actor->id]);
    }

    public function test_admin_without_create_permission_is_forbidden(): void
    {
        $admin = Admin::factory()->create();
        $admin->givePermissionTo(AdminPermission::AdminsView);

        $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/admins', [
                'name' => 'X',
                'email' => 'x@example.com',
                'password' => 'secret123',
                'roles' => [AdminRole::Moderator->value],
            ])
            ->assertForbidden();
    }

    public function test_users_role_has_user_permissions(): void
    {
        $role = Role::findByName(AdminRole::Users->value, 'admin');

        $this->assertEqualsCanonicalizing(
            [
                AdminPermission::UsersView->value,
                AdminPermission::UsersEdit->value,
                AdminPermission::UsersBlock->value,
            ],
            $role->permissions->pluck('name')->all(),
        );
    }

    public function test_admin_worker_role_has_admin_staff_permissions(): void
    {
        $role = Role::findByName(AdminRole::AdminWorker->value, 'admin');

        $this->assertEqualsCanonicalizing(
            [
                AdminPermission::AdminsView->value,
                AdminPermission::AdminsCreate->value,
                AdminPermission::AdminsUpdate->value,
                AdminPermission::AdminsDelete->value,
            ],
            $role->permissions->pluck('name')->all(),
        );
    }

    public function test_admin_creates_staff_with_multiple_roles_and_direct_permissions(): void
    {
        $actor = $this->makeAdmin();

        $response = $this->actingAs($actor, 'admin')
            ->postJson('/api/admin/admins', [
                'name' => 'Оператор пользователей',
                'email' => 'users-op@example.com',
                'password' => 'secret123',
                'roles' => [AdminRole::Users->value, AdminRole::Moderator->value],
                'permissions' => [AdminPermission::AdminsView->value],
            ])
            ->assertCreated();

        $this->assertEqualsCanonicalizing(
            [AdminRole::Users->value, AdminRole::Moderator->value],
            $response->json('roles'),
        );
        $this->assertContains(AdminPermission::AdminsView->value, $response->json('direct_permissions'));

        $created = Admin::query()->where('email', 'users-op@example.com')->first();
        $this->assertTrue($created->hasRole(AdminRole::Users));
        $this->assertTrue($created->hasRole(AdminRole::Moderator));
        $this->assertTrue($created->hasDirectPermission(AdminPermission::AdminsView));
        $this->assertTrue($created->hasPermissionTo(AdminPermission::UsersEdit));
    }

    public function test_update_syncs_direct_permissions(): void
    {
        $actor = $this->makeAdmin();
        $target = Admin::factory()->create();
        $target->assignRole(AdminRole::Users);
        $target->givePermissionTo(AdminPermission::AdminsView);

        $this->actingAs($actor, 'admin')
            ->putJson('/api/admin/admins/'.$target->id, [
                'name' => $target->name,
                'email' => $target->email,
                'roles' => [AdminRole::Users->value],
                'permissions' => [AdminPermission::OrdersView->value],
            ])
            ->assertOk()
            ->assertJsonPath('direct_permissions.0', AdminPermission::OrdersView->value);

        $target->refresh();
        $this->assertFalse($target->hasDirectPermission(AdminPermission::AdminsView));
        $this->assertTrue($target->hasDirectPermission(AdminPermission::OrdersView));
        $this->assertTrue($target->hasPermissionTo(AdminPermission::UsersView));
    }

    public function test_catalog_lists_roles_and_permissions(): void
    {
        $actor = $this->makeAdmin();

        $this->actingAs($actor, 'admin')
            ->getJson('/api/admin/admins/catalog')
            ->assertOk()
            ->assertJsonFragment(['value' => AdminRole::Users->value, 'label' => 'Пользователи'])
            ->assertJsonFragment(['value' => AdminRole::AdminWorker->value, 'label' => 'Работа с администраторами'])
            ->assertJsonFragment(['value' => AdminPermission::UsersView->value, 'label' => 'Просмотр пользователей'])
            ->assertJsonFragment(['value' => AdminPermission::UsersEdit->value, 'label' => 'Редактирование пользователей'])
            ->assertJsonFragment(['value' => AdminPermission::UsersBlock->value, 'label' => 'Блокировка пользователей']);
    }

    public function test_guest_cannot_see_catalog(): void
    {
        $this->getJson('/api/admin/admins/catalog')->assertUnauthorized();
    }

    private function makeAdmin(AdminRole $role = AdminRole::SuperAdmin): Admin
    {
        $admin = Admin::factory()->create();
        $admin->assignRole($role);

        return $admin;
    }
}
