<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RolController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\TabsController;
use App\Http\Controllers\DestinationController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\TripPositionController;
use App\Http\Controllers\TripRatingController;
use App\Http\Controllers\AuthController;

// Public
Route::get('/destinations', [DestinationController::class, 'index']);
Route::get('/destinations/{id}', [DestinationController::class, 'show']);
Route::post('/check-email', [AuthController::class, 'checkEmail']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Authenticated
Route::middleware('auth:api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('is_active')->group(function () {
        Route::get('/users', [AuthController::class, 'listUsers']);
        Route::post('/users/drivers', [AuthController::class, 'storeDriver']);
        Route::patch('/users/{id}/toggle-status', [AuthController::class, 'toggleStatus']);
        Route::put('/users/{id}', [AuthController::class, 'updateUser']);
        Route::delete('/users/{id}', [AuthController::class, 'deleteUser']);

        Route::apiResource('rols', RolController::class);
        Route::apiResource('states', StateController::class);
        Route::apiResource('tabs', TabsController::class);
        Route::post('/destinations', [DestinationController::class, 'store']);
        Route::put('/destinations/{id}', [DestinationController::class, 'update']);
        Route::delete('/destinations/{id}', [DestinationController::class, 'destroy']);
        Route::patch('/destinations/{id}/toggle-status', [DestinationController::class, 'toggleStatus']);

        Route::post('/trips/request', [TripController::class, 'request']);
        Route::post('/trips/{id}/accept', [TripController::class, 'accept']);
        Route::post('/trips/{id}/start', [TripController::class, 'start']);
        Route::post('/trips/{id}/finish', [TripController::class, 'finish']);
        Route::post('/trips/{id}/position', [TripPositionController::class, 'store']);
        Route::post('/trips/{id}/rate', [TripRatingController::class, 'store']);
        Route::get('/trips/history', [TripController::class, 'history']);
        Route::get('/ratings', [TripRatingController::class, 'index']);

        // Driver location
        Route::post('/driver/location', [AuthController::class, 'updateDriverLocation']);
        Route::post('/driver/offline', [AuthController::class, 'setDriverOffline']);
        Route::get('/drivers/nearby', [AuthController::class, 'getNearbyDrivers']);
    });
});
// Test Route to force broadcast
Route::post('/test-broadcast/{id}', function ($id) {
    echo "Broadcasting TripTaken for Trip $id...";
    $trip = \App\Models\Trip::find($id);
    if (!$trip)
        return response()->json(['error' => 'Trip not found'], 404);
    broadcast(new \App\Events\TripTaken($trip));
    return response()->json(['message' => 'Broadcast sent']);
});

Route::post('/test-broadcast-started/{id}', function ($id) {
    $trip = \App\Models\Trip::find($id);
    if (!$trip)
        return response()->json(['error' => 'Trip not found'], 404);

    // Force state for payload correctness simulation if needed, or just broadcast
    broadcast(new \App\Events\TripStarted($trip));
    return response()->json(['message' => 'TripStarted Broadcast sent']);
});
