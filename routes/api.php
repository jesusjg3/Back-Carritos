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

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Auth Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Logout (Protected by auth but allowing inactive users to logout)
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Rutas protegidas por autenticación y usuario activo
Route::middleware(['auth:sanctum', 'is_active'])->group(function () {

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

