<?php

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
