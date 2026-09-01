<?php

use App\Http\Controllers\Api\V1\AccessLogController;
use App\Http\Controllers\Api\V1\ExerciseController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\MemberController;
use App\Http\Controllers\Api\V1\MemberSubscriptionController;
use App\Http\Controllers\Api\V1\PingController;
use App\Http\Controllers\Api\V1\SubscriptionPlanController;
use App\Http\Middleware\EnsureApiEnabled;
use Illuminate\Support\Facades\Route;

/*
 * Superficie API HTTP JSON — prefisso /api applicato dal framework.
 *
 * Struttura:
 *   GET /api/v1/ping  — health check pubblico, non richiede auth ne' flag
 *   Tutti gli altri endpoint: auth:sanctum + kill switch EnsureApiEnabled
 *
 * Abilities richieste per endpoint (check via middleware 'abilities:'):
 *   members:read        — GET /members, GET /members/{id}
 *   members:read        — GET /members/{id}/subscription
 *   members:medical-read — filtro cert_expiry_before in /members (validato in FormRequest)
 *   access-logs:read    — GET /access-logs
 *   exercises:read      — GET /exercises, GET /exercises/{slug}
 *   subscription-plans:read — GET /subscription-plans
 */

Route::get('/v1/ping', PingController::class)->withoutMiddleware(EnsureApiEnabled::class);

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::get('/me', MeController::class);

    Route::middleware('abilities:subscription-plans:read')->group(function (): void {
        Route::get('/subscription-plans', [SubscriptionPlanController::class, 'index']);
    });

    Route::middleware('abilities:members:read')->group(function (): void {
        Route::get('/members', [MemberController::class, 'index']);
        Route::get('/members/{member}', [MemberController::class, 'show']);
        Route::get('/members/{member}/subscription', [MemberSubscriptionController::class, 'show']);
    });

    Route::middleware('abilities:access-logs:read')->group(function (): void {
        Route::get('/access-logs', [AccessLogController::class, 'index']);
    });

    Route::middleware('abilities:exercises:read')->group(function (): void {
        Route::get('/exercises', [ExerciseController::class, 'index']);
        Route::get('/exercises/{exercise}', [ExerciseController::class, 'show']);
    });
});
