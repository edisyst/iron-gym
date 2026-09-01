<?php

use App\Http\Middleware\EnsureApiEnabled;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias per i middleware spatie/laravel-permission
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'api.enabled' => EnsureApiEnabled::class,
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);

        // Gruppo api: throttle dedicato + kill switch.
        // auth:sanctum e' applicato per-route, non a livello di gruppo.
        $middleware->throttleApi('api');
        $middleware->api(prepend: [EnsureApiEnabled::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Eccezioni non critiche: non segnalate a Flare
        $exceptions->dontReport([
            AuthorizationException::class,
            ValidationException::class,
            ModelNotFoundException::class,
        ]);

        // Formato errore uniforme per tutte le risposte API.
        // Aggiunge la chiave "code" stabile a ogni risposta di errore;
        // mantiene "errors" per la validazione (formato nativo Laravel).
        // Non tocca le risposte HTML del backoffice e della PWA.

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => 'Non autenticato.',
                'code' => 'unauthenticated',
            ], 401);
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => 'Operazione non autorizzata.',
                'code' => 'forbidden',
            ], 403);
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => 'Risorsa non trovata.',
                'code' => 'not_found',
            ], 404);
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'validation_failed',
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $code = match ($e->getStatusCode()) {
                429 => 'rate_limited',
                403 => 'forbidden',
                404 => 'not_found',
                503 => 'api_disabled',
                default => 'error',
            };

            return response()->json([
                'message' => $e->getMessage() ?: 'Errore del server.',
                'code' => $code,
            ], $e->getStatusCode(), $e->getHeaders());
        });
    })->create();
