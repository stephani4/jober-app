<?php

namespace Database\Seeders;

use App\Enums\AdminPermission;
use App\Enums\AdminRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Роли и права guard=admin.
 */
class AdminPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'admin';
        $permissions = array_map(
            fn (AdminPermission $permission) => Permission::findOrCreate($permission->value, $guard),
            AdminPermission::cases(),
        );

        $superAdmin = Role::findOrCreate(AdminRole::SuperAdmin->value, $guard);
        $superAdmin->syncPermissions($permissions);

        // Модератор видит заказы и список пользователей, но не управляет сотрудниками
        // и не редактирует/блокирует пользователей приложения.
        $moderatorPermissionNames = [
            AdminPermission::OrdersView->value,
            AdminPermission::OrdersApprove->value,
            AdminPermission::OrdersCancel->value,
            AdminPermission::UsersView->value,
        ];
        $moderator = Role::findOrCreate(AdminRole::Moderator->value, $guard);
        $moderator->syncPermissions($moderatorPermissionNames);

        $users = Role::findOrCreate(AdminRole::Users->value, $guard);
        $users->syncPermissions([
            AdminPermission::UsersView->value,
            AdminPermission::UsersEdit->value,
            AdminPermission::UsersBlock->value,
        ]);

        $adminWorker = Role::findOrCreate(AdminRole::AdminWorker->value, $guard);
        $adminWorker->syncPermissions(
            array_map(
                fn (AdminPermission $permission) => $permission->value,
                array_values(array_filter(
                    AdminPermission::cases(),
                    fn (AdminPermission $permission) => $permission->isStaffManagement(),
                )),
            ),
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
