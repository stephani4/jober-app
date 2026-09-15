<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Tests\TestCase;

class EnsureUserHasRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_reach_executor_only_route(): void
    {
        $this->registerRoute('executor-only', 'user.role:executor');

        $this->getJson('/api/testing/executor-only')->assertUnauthorized();
    }

    public function test_customer_cannot_reach_executor_only_route(): void
    {
        $this->registerRoute('executor-only', 'user.role:executor');

        $this->actingAs(User::factory()->create(['role' => UserRole::Customer]), 'api')
            ->getJson('/api/testing/executor-only')
            ->assertForbidden();
    }

    public function test_executor_reaches_executor_only_route(): void
    {
        $this->registerRoute('executor-only', 'user.role:executor');

        $this->actingAs(User::factory()->create(['role' => UserRole::Executor]), 'api')
            ->getJson('/api/testing/executor-only')
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_route_with_several_roles_allows_every_listed_role(): void
    {
        $this->registerRoute('both-roles', 'user.role:customer,executor');

        foreach (UserRole::cases() as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]), 'api')
                ->getJson('/api/testing/both-roles')
                ->assertOk();
        }
    }

    public function test_unknown_role_in_route_definition_fails_fast(): void
    {
        $this->registerRoute('unknown-role', 'user.role:manager');
        $this->withoutExceptionHandling();

        $this->expectException(InvalidArgumentException::class);

        $this->actingAs(User::factory()->create(['role' => UserRole::Executor]), 'api')
            ->getJson('/api/testing/unknown-role');
    }

    /**
     * Регистрирует тестовый маршрут с проверяемым middleware.
     */
    private function registerRoute(string $path, string $middleware): void
    {
        Route::middleware(['auth:api', $middleware])
            ->get("/api/testing/{$path}", fn () => response()->json(['ok' => true]));
    }
}