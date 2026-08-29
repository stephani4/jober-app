<?php

namespace Database\Seeders;

use App\Enums\AdminRole;
use App\Models\Admin;
use Illuminate\Database\Seeder;

/**
 * Первый сотрудник админки. Пароль задаётся через ADMIN_PASSWORD.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminPermissionSeeder::class);

        $admin = Admin::query()->updateOrCreate(
            ['email' => (string) env('ADMIN_EMAIL', 'admin@jober.local')],
            [
                'name' => (string) env('ADMIN_NAME', 'Администратор'),
                'password' => (string) env('ADMIN_PASSWORD', 'password'),
            ],
        );

        $admin->syncRoles([AdminRole::SuperAdmin]);
    }
}
