<?php

namespace App\Providers;

use App\Enums\AdminRole;
use App\Models\Admin;
use App\Moderation\ProfanityModerationRule;
use App\Services\Centrifugo\CentrifugoClient;
use App\Services\Centrifugo\CentrifugoTokenService;
use App\Services\OrderModerationService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Новые правила: класс OrderModerationRule и тег order.moderation.
        // Первое правило с причиной отклоняет заказ.
        $this->app->tag([
            ProfanityModerationRule::class,
        ], 'order.moderation');

        $this->app->singleton(OrderModerationService::class, function ($app) {
            return new OrderModerationService(
                $app->tagged('order.moderation'),
                $app->make(CentrifugoClient::class),
                $app->make(CentrifugoTokenService::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, string $ability) {
            if ($user instanceof Admin && $user->hasRole(AdminRole::SuperAdmin)) {
                return true;
            }

            return null;
        });
    }
}
