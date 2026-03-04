<?php

use App\Http\Controllers\GatewayController;
use Illuminate\Support\Facades\Route;

// Auth Service routes - public
Route::any('/auth/{path?}', [GatewayController::class, 'forwardToAuth'])
    ->where('path', '.*');

// IP Service routes - just forward, let IP service validate JWT
Route::any('/ip-addresses/{path?}', [GatewayController::class, 'forwardToIp'])
    ->where('path', '.*');

Route::any('/audit/auth', [GatewayController::class, 'forwardToAuth'])
    ->defaults('path', 'audit-logs');

Route::any('/audit/ip', [GatewayController::class, 'forwardToIp'])
    ->defaults('path', 'audit-logs');
