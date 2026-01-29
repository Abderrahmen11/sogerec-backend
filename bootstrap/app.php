<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/health',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Allow Railway health check host
        $middleware->trustHosts(at: [
            'healthcheck.railway.app',
            'sogerec-backend-production.up.railway.app',
        ], subdomains: false);

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);

        // Exclude CSRF token verification for all API routes (using tokens)
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'health',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle unauthenticated requests to API routes
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Unauthenticated.'
                ], 401);
            }
        });
    })->create();
