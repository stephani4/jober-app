<?php

use App\Enums\AdminPermission;
use App\Http\Controllers\Api\Admin\AdminAdministratorController;
use App\Http\Controllers\Api\Admin\AdminAuthController;
use App\Http\Controllers\Api\Admin\AdminOrderController;
use App\Http\Controllers\Api\Admin\AdminOrderTypeController;
use App\Http\Controllers\Api\Admin\AdminRealtimeController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AdminAuthController::class, 'login']);

        Route::middleware('auth:admin')->group(function () {
            Route::get('/me', [AdminAuthController::class, 'me']);
            Route::post('/logout', [AdminAuthController::class, 'logout']);
        });
    });

    Route::middleware('auth:admin')->group(function () {
        Route::get('/orders', [AdminOrderController::class, 'index'])
            ->middleware('permission:'.AdminPermission::OrdersView->value.',admin');
        // Страница заказа /admin/orders/{id}/show: общая информация и вкладка «Наблюдение».
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])
            ->middleware('permission:'.AdminPermission::OrdersView->value.',admin');
        Route::get('/orders/{order}/executing', [AdminOrderController::class, 'executing'])
            ->middleware('permission:'.AdminPermission::OrdersView->value.',admin');
        Route::get('/order-types', [AdminOrderTypeController::class, 'index'])
            ->middleware('permission:'.AdminPermission::OrdersView->value.',admin');
        Route::get('/realtime/token', AdminRealtimeController::class)
            ->middleware('permission:'.AdminPermission::OrdersView->value.',admin');
        Route::post('/orders/{order}/approve', [AdminOrderController::class, 'approve'])
            ->middleware('permission:'.AdminPermission::OrdersApprove->value.',admin');
        Route::post('/orders/{order}/cancel', [AdminOrderController::class, 'cancel'])
            ->middleware('permission:'.AdminPermission::OrdersCancel->value.',admin');

        Route::get('/users', [AdminUserController::class, 'index'])
            ->middleware('permission:'.AdminPermission::UsersView->value.',admin');

        Route::get('/admins/catalog', [AdminAdministratorController::class, 'catalog']);
        Route::get('/admins', [AdminAdministratorController::class, 'index'])
            ->middleware('permission:'.AdminPermission::AdminsView->value.',admin');
        Route::post('/admins', [AdminAdministratorController::class, 'store'])
            ->middleware('permission:'.AdminPermission::AdminsCreate->value.',admin');
        Route::get('/admins/{admin}', [AdminAdministratorController::class, 'show'])
            ->middleware('permission:'.AdminPermission::AdminsView->value.',admin');
        Route::put('/admins/{admin}', [AdminAdministratorController::class, 'update'])
            ->middleware('permission:'.AdminPermission::AdminsUpdate->value.',admin');
        Route::delete('/admins/{admin}', [AdminAdministratorController::class, 'destroy'])
            ->middleware('permission:'.AdminPermission::AdminsDelete->value.',admin');
    });
});
