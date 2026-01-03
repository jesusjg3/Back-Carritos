<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RolController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\TabsController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\TripPositionController;
use App\Http\Controllers\TripRatingController;
use App\Http\Controllers\AuthController;

// ============ Public Routes (No authentication required) ============
// Obtener roles disponibles
Route::get('/roles', [AuthController::class, 'getRoles']);

// Auth Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Verificar disponibilidad de email
Route::post('/check-email', [AuthController::class, 'checkEmail']);

// ============ Protected Routes (Requires authentication) ============
Route::middleware('auth:api')->group(function () {
    // Get current user info
    Route::get('/me', [AuthController::class, 'me']);

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Rutas protegidas por usuario activo
    Route::middleware('is_active')->group(function () {

        // Admin Users Management
        Route::get('/users', [AuthController::class, 'listUsers']);
        Route::post('/users/drivers', [AuthController::class, 'storeDriver']);
        Route::patch('/users/{id}/toggle-status', [AuthController::class, 'toggleStatus']);

        // Roles
        Route::apiResource('rols', RolController::class);

        // States
        Route::apiResource('states', StateController::class);

        // Tabs
        Route::apiResource('tabs', TabsController::class);

        // Trips
        Route::post('/trips/request', [TripController::class, 'request']);
        Route::post('/trips/{id}/accept', [TripController::class, 'accept']);
        Route::post('/trips/{id}/finish', [TripController::class, 'finish']);

        // Trip Positions
        Route::post('/trips/{id}/position', [TripPositionController::class, 'store']);

        // Trip Ratings
        Route::post('/trips/{id}/rate', [TripRatingController::class, 'store']);
    });
});

