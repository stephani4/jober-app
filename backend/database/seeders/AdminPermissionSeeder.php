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

        $moderator = Role::findOrCreate(AdminRole::Moderator->value, $guard);
        $moderator->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
