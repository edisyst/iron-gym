<?php

use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\PingController;
use App\Http\Middleware\EnsureApiEnabled;
use Illuminate\Support\Facades\Route;

/*
 * Superficie API HTTP JSON — prefisso /api applicato dal framework.
 *
 * Struttura:
 *   GET /api/v1/ping  — health check pubblico, non richiede auth ne' flag
 *   GET /api/v1/me    — identita' del token, richiede auth:sanctum
 *
 * Tutti gli altri endpoint stanno dentro il gruppo v1 con kill switch.
 * Il kill switch (EnsureApiEnabled) e' applicato a livello di gruppo api
 * in bootstrap/app.php, tranne /ping che e' escluso esplicitamente.
 */

Route::get('/v1/ping', PingController::class)->withoutMiddleware(EnsureApiEnabled::class);

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::get('/me', MeController::class);
});
