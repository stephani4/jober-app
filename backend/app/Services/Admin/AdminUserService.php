<?php

namespace App\Services\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Список пользователей приложения для админки с фильтрами и пагинацией.
 */
class AdminUserService
{
    public const PAGE_SIZE = 15;

    /**
     * @param  array<string, mixed>  $data
     * @return array{items: Collection<int, User>, next_cursor: int|null}
     */
    public function list(array $data): array
    {
        $payload = $this->validateList($data);
        $limit = self::PAGE_SIZE;

        $query = User::query()->with('avatar')->orderByDesc('id');

        if (! empty($payload['name'])) {
            $query->where(DB::raw('LOWER(name)'), 'like', '%'.mb_strtolower($this->escapeLike($payload['name'])).'%');
        }

        if (! empty($payload['email'])) {
            $query->where(DB::raw('LOWER(email)'), 'like', '%'.mb_strtolower($this->escapeLike($payload['email'])).'%');
        }

        if ($payload['birth_date'] !== null) {
            $query->whereDate('birth_date', $payload['birth_date']);
        }

        if ($payload['role'] !== null) {
            $query->where('role', $payload['role']->value);
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

    /**
     * Экранирует LIKE-джокеры, чтобы пользователь искал по буквам, а не по шаблонам.
     */
    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{name: string|null, email: string|null, birth_date: string|null, role: UserRole|null, cursor: int|null}
     */
    private function validateList(array $data): array
    {
        $validator = Validator::make($data, [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date_format:Y-m-d'],
            'role' => ['nullable', 'string', Rule::in([
                UserRole::Customer->value,
                UserRole::Executor->value,
            ])],
            'cursor' => ['nullable', 'integer', 'min:1'],
        ], [
            'birth_date.date_format' => 'Дата рождения должна быть в формате ГГГГ-ММ-ДД.',
            'role.in' => 'Выберите корректную роль.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        /** @var array{name?: string, email?: string, birth_date?: string, role?: string, cursor?: int} $validated */
        $validated = $validator->validated();

        return [
            'name' => $validated['name'] ?? null,
            'email' => $validated['email'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'role' => isset($validated['role']) ? UserRole::from($validated['role']) : null,
            'cursor' => $validated['cursor'] ?? null,
        ];
    }
}
