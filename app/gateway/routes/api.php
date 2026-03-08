<?php

use App\Http\Controllers\GatewayController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->group(function () {
    // Auth Service routes - public
    Route::any('/auth/{path?}', [GatewayController::class, 'forwardToAuth'])
        ->where('path', '.*');

    // IP Service routes - just forward, let IP service validate JWT
    Route::any('/ip-addresses/{path?}', [GatewayController::class, 'forwardToIp'])
        ->where('path', '.*');

    Route::any('/audit/auth', fn (Request $r) => app(GatewayController::class)->forwardToAuth($r, 'audit-logs'));
});

Route::any('/audit/ip', fn (Request $r) => app(GatewayController::class)->forwardToIp($r, 'audit-logs'));
