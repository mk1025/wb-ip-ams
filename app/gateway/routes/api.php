<?php

declare(strict_types=1);

use App\Http\Controllers\GatewayController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->group(function () {
    // Auth Service routes - public
    Route::any('/auth/{path?}', [GatewayController::class, 'forwardToAuth'])
        ->where('path', '[a-zA-Z0-9\-_\/]*');

    // IP Service routes - just forward, let IP service validate JWT
    Route::any('/ip-addresses/{path?}', [GatewayController::class, 'forwardToIp'])
        ->where('path', '[a-zA-Z0-9\-_\/]*');

    Route::any('/audit/auth', [GatewayController::class, 'forwardAuditToAuth']);
    Route::any('/audit/ip', [GatewayController::class, 'forwardAuditToIp']);
});
