<?php

use App\Http\Middleware\VerifyInternalHmac;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'internal.hmac' => VerifyInternalHmac::class,
        ]);

        // Behind the host nginx reverse proxy (questmap.nekoserver.es -> :8090).
        // Trust its X-Forwarded-* headers so the app knows requests are HTTPS.
        $middleware->trustProxies(at: '*', headers:
            Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO,
        );

        // API is stateless: never redirect unauthenticated api/* to a web login
        // route (there isn't one) — let it fall through to a 401 JSON response.
        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('api/*') ? null : '/login',
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Force JSON for the API surface (App\Exceptions\ApiException renders
        // itself; this keeps framework exceptions JSON too).
        $exceptions->shouldRenderJsonWhen(
            fn ($request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Unauthenticated API calls must return 401 JSON, never a redirect to a
        // (non-existent) web login route — even without an Accept header.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'No autenticado.',
                    'error_code' => 'unauthenticated',
                ], 401);
            }
        });
    })->create();
