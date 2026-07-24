<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RolController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\DestinationController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\TripPositionController;
use App\Http\Controllers\TripRatingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DriverLocationController;
use App\Http\Controllers\ReportController;

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

    Route::post('/devices', [\App\Http\Controllers\DeviceController::class, 'store']);

    Route::middleware('is_active')->group(function () {
        // Rutas Exclusivas para el Administrador
        Route::middleware('role:admin')->group(function () {
            Route::middleware('permission:view_dashboard')->group(function () {
                Route::get('/dashboard/stats', [ReportController::class, 'dashboardStats']);
                Route::get('/reports/all-summary', [ReportController::class, 'allSummary']);
            });

            Route::middleware('permission:view_driver_reports')->group(function () {
                Route::get('/reports/drivers-summary', [ReportController::class, 'driversSummary']);
                Route::get('/reports/ratings-distribution', [ReportController::class, 'ratingsDistribution']);
            });

            Route::middleware('permission:view_route_reports')->group(function () {
                Route::get('/reports/destinations-summary', [ReportController::class, 'destinationsSummary']);
                Route::get('/reports/hourly-summary', [ReportController::class, 'hourlySummary']);
                Route::get('/reports/daily-summary', [ReportController::class, 'dailySummary']);
                Route::get('/reports/routes-performance', [ReportController::class, 'routesPerformance']);
            });

            Route::middleware('permission:manage_users')->group(function () {
                Route::get('/users', [UserController::class, 'listUsers']);
                Route::post('/users/drivers', [UserController::class, 'storeDriver']);
                Route::post('/users/admins', [UserController::class, 'storeAdmin']);
                Route::patch('/users/{id}/toggle-status', [UserController::class, 'toggleStatus']);
                Route::put('/users/{id}', [UserController::class, 'updateUser']);
                Route::delete('/users/{id}', [UserController::class, 'deleteUser']);
                Route::post('/users/{id}/restore', [UserController::class, 'restoreUser']);
            });

            Route::apiResource('rols', RolController::class);
            Route::apiResource('states', StateController::class);
            Route::apiResource('vehicles', \App\Http\Controllers\VehicleController::class);
            Route::patch('/vehicles/{id}/toggle-status', [\App\Http\Controllers\VehicleController::class, 'toggleStatus']);
            Route::post('/destinations', [DestinationController::class, 'store']);
            Route::put('/destinations/{id}', [DestinationController::class, 'update']);
            Route::delete('/destinations/{id}', [DestinationController::class, 'destroy']);
            Route::post('/destinations/{id}/restore', [DestinationController::class, 'restore']);
            Route::patch('/destinations/{id}/toggle-status', [DestinationController::class, 'toggleStatus']);
        });

        Route::post('/trips/request', [TripController::class, 'request']);
        Route::post('/trips/{id}/accept', [TripController::class, 'accept']);
        Route::post('/trips/{id}/start', [TripController::class, 'start']);
        Route::post('/trips/{tripId}/board/{passengerId}', [TripController::class, 'boardPassenger']);
        Route::post('/trips/{tripId}/dropoff/{passengerId}', [TripController::class, 'dropOffPassenger']);
        Route::post('/trips/{id}/finish', [TripController::class, 'finish']);
        Route::delete('/trips/{id}/cancel', [TripController::class, 'cancel']);
        Route::post('/trips/{id}/position', [TripPositionController::class, 'store']);
        Route::post('/trips/{id}/rate', [TripRatingController::class, 'store']);
        Route::get('/trips', [TripController::class, 'index']); // Historial Admin Completo
        Route::get('/trips/history', [TripController::class, 'history']);
        Route::get('/ratings', [TripRatingController::class, 'index']);

        Route::post('/driver/location', [DriverLocationController::class, 'updateDriverLocation']);
        Route::post('/driver/offline', [DriverLocationController::class, 'setDriverOffline']);
        Route::get('/drivers/nearby', [DriverLocationController::class, 'getNearbyDrivers']);
        Route::get('/users/drivers', [DriverLocationController::class, 'getOnlineDrivers']);
    });
});
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

    broadcast(new \App\Events\TripStarted($trip));
    return response()->json(['message' => 'TripStarted Broadcast sent']);
});
