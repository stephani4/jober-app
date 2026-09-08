<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderMessageController;
use App\Http\Controllers\Api\OrderTypeController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\RealtimeTokenController;
use App\Http\Controllers\Api\WorkingAreaController;
use App\Http\Controllers\Centrifugo\RpcController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'jober-api',
    ]);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:api')->group(function () {
    Route::get('/realtime/token', RealtimeTokenController::class);
    Route::get('/order-types', [OrderTypeController::class, 'index']);
    Route::get('/push/vapid', [PushSubscriptionController::class, 'vapid']);
    Route::post('/push/subscriptions', [PushSubscriptionController::class, 'store']);
    Route::delete('/push/subscriptions', [PushSubscriptionController::class, 'destroy']);
    Route::get('/orders/{order}/messages', [OrderMessageController::class, 'index']);
    Route::post('/orders/{order}/messages', [OrderMessageController::class, 'store']);
});

Route::prefix('admin')->middleware('auth:admin')->group(function () {
    // Working Areas CRUD
    Route::apiResource('working-areas', WorkingAreaController::class);
});

Route::post('/centrifugo/rpc', RpcController::class)->middleware('centrifugo.proxy');
