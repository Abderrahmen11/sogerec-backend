<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\InterventionController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminStatsController;

// Sanctum routes with session support but without traditional CSRF verification
Route::middleware(['web'])->group(function () {
    // Sanctum CSRF Cookie Route - accept GET and POST so browser and tools work
    Route::match(['get', 'post'], '/sanctum/csrf-cookie', function (Request $request) {
        // Accessing session forces Laravel to initialize it and set cookies
        $request->session()->regenerateToken();
        return response()->noContent();
    });

    // Public auth routes
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

// Protected API routes with Sanctum auth
Route::middleware(['web', 'auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', fn(Request $request) => $request->user());

    // Users - Admin only
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/stats', [AdminStatsController::class, 'index']);
        Route::apiResource('users', UserController::class);
        Route::put('/users/profile', [UserController::class, 'updateProfile']);
        Route::post('/users/change-password', [UserController::class, 'changePassword']);
    });

    // Tickets - All authenticated users (internal controller handles filtering)
    Route::get('/tickets/search', [TicketController::class, 'search']);
    Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus']);
    Route::post('/tickets/{ticket}/comments', [TicketController::class, 'addComment']);
    Route::apiResource('tickets', TicketController::class);

    // Interventions - All authenticated users (internal controller handles filtering for index/show)
    Route::get('/interventions', [InterventionController::class, 'index']);
    Route::get('/interventions/{id}', [InterventionController::class, 'show']);

    // Interventions - Management (Admins & Technicians)
    Route::middleware('role:technician,admin')->group(function () {
        Route::get('/interventions/planning', [InterventionController::class, 'planning']);
        Route::patch('/interventions/{id}/status', [InterventionController::class, 'updateStatus']);
        Route::post('/interventions/{id}/report', [InterventionController::class, 'submitReport']);
    });

    // Interventions - Admin only
    Route::middleware('role:admin')->group(function () {
        Route::post('/interventions', [InterventionController::class, 'store']);
        Route::put('/interventions/{id}', [InterventionController::class, 'update']);
        Route::delete('/interventions/{id}', [InterventionController::class, 'destroy']);
    });


    // Notifications - All authenticated users
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::apiResource('notifications', NotificationController::class)->only(['index', 'destroy']);
});
