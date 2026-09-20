<?php

namespace App\Services\Admin;

use App\Enums\AdminPermission;
use App\Enums\AdminRole;
use App\Models\Admin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * CRUD сотрудников админки: список, создание, обновление, удаление.
 */
class AdminAdministratorService
{
    public const PAGE_SIZE = 15;

    /**
     * @param  array<string, mixed>  $data
     * @return array{items: Collection<int, Admin>, next_cursor: int|null}
     */
    public function list(array $data): array
    {
        $payload = $this->validateList($data);
        $limit = self::PAGE_SIZE;

        $query = Admin::query()
            ->with(['roles.permissions', 'permissions'])
            ->orderByDesc('id');

        if (! empty($payload['name'])) {
            $query->where(DB::raw('LOWER(name)'), 'like', '%'.mb_strtolower($this->escapeLike($payload['name'])).'%');
        }

        if (! empty($payload['email'])) {
            $query->where(DB::raw('LOWER(email)'), 'like', '%'.mb_strtolower($this->escapeLike($payload['email'])).'%');
        }

        if ($payload['role'] !== null) {
            $query->role($payload['role']->value);
        }

        if ($payload['cursor'] !== null) {
            $query->where('id', '<', $payload['cursor']);
        }

        $rows = $query->limit($limit + 1)->get();
        $hasMore = $rows->count() > $limit;
        $page = $hasMore ? $rows->take($limit)->values() : $rows->values();

        return [
            'items' => $page,
            'next_cursor' => $hasMore ? (int) $page->last()->id : null,
        ];
    }

    public function find(Admin $admin): Admin
    {
        return $admin->load(['roles.permissions', 'permissions']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Admin
    {
        $payload = $this->validateWrite($data, null);

        $admin = Admin::query()->create([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'password' => $payload['password'],
        ]);

        $admin->syncRoles($payload['roles']);
        $admin->syncPermissions($payload['permissions']);

        return $admin->load(['roles.permissions', 'permissions']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Admin $admin, array $data): Admin
    {
        $payload = $this->validateWrite($data, $admin);

        $this->assertCanChangeSuperAdminRole($admin, $payload['roles']);

        $attributes = [
            'name' => $payload['name'],
            'email' => $payload['email'],
        ];

        if ($payload['password'] !== null) {
            $attributes['password'] = $payload['password'];
        }

        $admin->update($attributes);
        $admin->syncRoles($payload['roles']);
        $admin->syncPermissions($payload['permissions']);

        return $admin->fresh(['roles.permissions', 'permissions']);
    }

    public function delete(Admin $admin, Admin $actor): void
    {
        if ($admin->is($actor)) {
            throw ValidationException::withMessages([
                'id' => ['Нельзя удалить собственную учётную запись.'],
            ]);
        }

        $this->assertCanChangeSuperAdminRole($admin, []);

        $admin->delete();
    }

    /**
     * Нельзя снять роль супер-админа, если других супер-админов не останется.
     *
     * @param  list<string>  $nextRoles
     */
    private function assertCanChangeSuperAdminRole(Admin $admin, array $nextRoles): void
    {
        $wasSuper = $admin->hasRole(AdminRole::SuperAdmin);
        $willBeSuper = in_array(AdminRole::SuperAdmin->value, $nextRoles, true);

        if (! $wasSuper || $willBeSuper) {
            return;
        }

        $hasOtherSuper = Admin::role(AdminRole::SuperAdmin->value)
            ->whereKeyNot($admin->id)
            ->exists();

        if (! $hasOtherSuper) {
            throw ValidationException::withMessages([
                'roles' => ['Нельзя лишить роли последнего суперадмина.'],
            ]);
        }
    }

    /**
     * Экранирует LIKE-джокеры, чтобы пользователь искал по буквам, а не по шаблонам.
     */
    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{name: string|null, email: string|null, role: AdminRole|null, cursor: int|null}
     */
    private function validateList(array $data): array
    {
        $validator = Validator::make($data, [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', Rule::in(AdminRole::values())],
            'cursor' => ['nullable', 'integer', 'min:1'],
        ], [
            'role.in' => 'Выберите корректную роль.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        /** @var array{name?: string, email?: string, role?: string, cursor?: int} $validated */
        $validated = $validator->validated();

        return [
            'name' => $validated['name'] ?? null,
            'email' => $validated['email'] ?? null,
            'role' => isset($validated['role']) ? AdminRole::from($validated['role']) : null,
            'cursor' => $validated['cursor'] ?? null,
        ];
    }

    /**
     * Справочник ролей и всех разрешений для формы сотрудника.
     *
     * @return array{roles: list<array{value: string, label: string}>, permissions: list<array{value: string, label: string}>}
     */
    public function catalog(): array
    {
        return [
            'roles' => array_map(
                fn (AdminRole $role) => [
                    'value' => $role->value,
                    'label' => $role->label(),
                ],
                AdminRole::cases(),
            ),
            'permissions' => array_map(
                fn (AdminPermission $permission) => [
                    'value' => $permission->value,
                    'label' => $permission->label(),
                ],
                AdminPermission::cases(),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{name: string, email: string, password: string|null, roles: list<string>, permissions: list<string>}
     */
    private function validateWrite(array $data, ?Admin $admin): array
    {
        $creating = $admin === null;

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('admins', 'email')->ignore($admin?->id),
            ],
            'password' => [$creating ? 'required' : 'nullable', 'string', 'min:8'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', Rule::in(AdminRole::values())],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['required', 'string', Rule::in(AdminPermission::values())],
        ], [
            'name.required' => 'Укажите имя.',
            'email.required' => 'Укажите email.',
            'email.email' => 'Некорректный email.',
            'email.unique' => 'Сотрудник с таким email уже существует.',
            'password.required' => 'Укажите пароль.',
            'password.min' => 'Пароль должен быть не короче 8 символов.',
            'roles.required' => 'Выберите хотя бы одну роль.',
            'roles.min' => 'Выберите хотя бы одну роль.',
            'roles.*.in' => 'Выберите корректную роль.',
            'permissions.*.in' => 'Выберите корректное разрешение.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        /** @var array{name: string, email: string, password?: string, roles: list<string>, permissions?: list<string>} $validated */
        $validated = $validator->validated();

        $password = $validated['password'] ?? null;
        if (is_string($password) && $password === '') {
            $password = null;
        }

        return [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $password,
            'roles' => array_values(array_unique($validated['roles'])),
            'permissions' => array_values(array_unique($validated['permissions'] ?? [])),
        ];
    }
}
