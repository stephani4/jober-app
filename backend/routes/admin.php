<?php

use App\Enums\AdminPermission;
use App\Http\Controllers\Api\Admin\AdminAuthController;
use App\Http\Controllers\Api\Admin\AdminOrderController;
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
        Route::post('/orders/{order}/approve', [AdminOrderController::class, 'approve'])
            ->middleware('permission:'.AdminPermission::OrdersApprove->value.',admin');
        Route::post('/orders/{order}/cancel', [AdminOrderController::class, 'cancel'])
            ->middleware('permission:'.AdminPermission::OrdersCancel->value.',admin');
    });
});
