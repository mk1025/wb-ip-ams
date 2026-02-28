<?php

use App\Http\Controllers\Api\IpAddressController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:api')->prefix('ip-addresses')->group(function () {
    Route::get('/', [IpAddressController::class, 'index']);
    Route::post('/', [IpAddressController::class, 'store']);
    Route::get('/{id}', [IpAddressController::class, 'show']);
    Route::put('/{id}', [IpAddressController::class, 'update']);
    Route::delete('/{id}', [IpAddressController::class, 'destroy']);
});
