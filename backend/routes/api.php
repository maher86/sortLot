<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PackageController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    DB::connection()->getPdo();
    Redis::connection()->ping();

    return response()->json([
        'status' => 'ok',
        'db' => 'ok',
        'redis' => 'ok',
        'queue' => config('queue.default'),
    ]);
});

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/password', [AuthController::class, 'updatePassword']);
    });
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::patch('/packages/{package}/status', [PackageController::class, 'changeStatus']);
    Route::post('/packages/{package}/items/bulk', [PackageController::class, 'bulkItems']);
    Route::apiResource('packages', PackageController::class);
});
