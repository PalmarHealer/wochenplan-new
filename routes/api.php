<?php

use App\Http\Controllers\Api\ApiIndexController;
use App\Http\Controllers\Api\OpenApiController;
use App\Http\Controllers\Mcp\McpController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', ApiIndexController::class);
Route::get('/openapi.json', OpenApiController::class);

Route::middleware(['auth:sanctum', 'api.recent-login', 'api.access'])->group(function () {
    Route::get('/v1/ping', fn (Request $request) => response()->json([
        'ok' => true,
        'user_id' => $request->user()->id,
    ]));

    Route::get('/mcp', McpController::class);
});
