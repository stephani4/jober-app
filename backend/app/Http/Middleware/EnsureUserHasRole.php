<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ограничивает маршрут ролями пользователя приложения (`users.role`).
 *
 * Роли передаются параметром middleware: `->middleware('user.role:executor')`
 * или списком: `->middleware('user.role:customer,executor')`.
 *
 * Значения ролей берутся из enum UserRole, поэтому middleware поддерживает все его кейсы;
 * неизвестное имя роли — ошибка конфигурации маршрута (fail fast), а не отказ в доступе.
 * Роли персонала админки (Spatie, guard=admin) проверяют middleware
 * `role`, `permission`, `role_or_permission`.
 */
class EnsureUserHasRole
{
    /**
     * @param  string  ...$roles  Значения ролей из App\Enums\UserRole
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowed = $this->resolveRoles($roles);
        $user = $request->user();

        if (! $user instanceof User || $user->role === null || ! in_array($user->role, $allowed, true)) {
            return response()->json([
                'message' => 'Недостаточно прав для выполнения операции.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }

    /**
     * Преобразует роли из параметров маршрута в enum-кейсы, проверяя их существование.
     *
     * @param  list<string>  $roles
     * @return list<UserRole>
     */
    private function resolveRoles(array $roles): array
    {
        if ($roles === []) {
            throw new InvalidArgumentException('Middleware "user.role" требует хотя бы одну роль.');
        }

        return array_map(
            fn (string $role): UserRole => UserRole::tryFrom(trim($role))
                ?? throw new InvalidArgumentException(
                    sprintf('Роль "%s" не существует в %s.', $role, UserRole::class),
                ),
            $roles,
        );
    }
}