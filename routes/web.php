<?php

use Illuminate\Support\Facades\Route;

// Health check route for uptime monitoring
Route::get('/up', function () {
    return response()->json(['status' => 'ok']);
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => env('APP_NAME', 'Laravel'),
        'environment' => env('APP_ENV', 'production'),
        'time' => now()->toDateTimeString(),
    ], 200);
})->withoutMiddleware([\Illuminate\Auth\Middleware\Authenticate::class, \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

// API Documentation or main page
Route::get('/', function () {
    return response()->json([
        'message' => 'SogeFix API',
        'version' => '1.0.0',
        'status' => 'running',
        'endpoints' => [
            'auth' => '/api/login, /api/register, /api/logout, /api/sanctum/csrf-cookie',
            'tickets' => '/api/tickets',
            'interventions' => '/api/interventions',
            'notifications' => '/api/notifications',
            'users' => '/api/users',
        ]
    ]);
});

// Catch-all for 404s on web routes (SPA fallback would be handled by frontend)
Route::fallback(function () {
    return response()->json(['message' => 'API endpoint not found'], 404);
});
