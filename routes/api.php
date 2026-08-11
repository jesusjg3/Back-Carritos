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
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\VehicleController;

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

    Route::post('/devices', [DeviceController::class, 'store']);

    // Shifts, Events, and Assignments
    Route::apiResource('shifts', ShiftController::class);
    Route::patch('/shifts/{id}/toggle-status', [ShiftController::class, 'toggleStatus']);
    
    Route::apiResource('events', EventController::class);
    
    Route::apiResource('assignments', AssignmentController::class);
    Route::patch('/assignments/{id}/toggle-status', [AssignmentController::class, 'toggleStatus']);

    // Complaints
    Route::post('/complaints', [ComplaintController::class, 'store']);
    Route::get('/complaints', [ComplaintController::class, 'index']); // For admin
    Route::get('/complaints/{complaint}', [ComplaintController::class, 'show']);
    Route::patch('/complaints/{complaint}/status', [ComplaintController::class, 'updateStatus']); // For admin

    Route::middleware('is_active')->group(function () {
        // Rutas con Permisos Granulares (Admin o Usuarios con el permiso)
        Route::middleware('permission:view_dashboard,view_driver_reports,view_route_reports,view_passenger_reports')->group(function () {
            Route::get('/reports/all-summary', [ReportController::class, 'allSummary']);
        });

        Route::middleware('permission:view_dashboard')->group(function () {
            Route::get('/dashboard/stats', [ReportController::class, 'dashboardStats']);
        });

        Route::middleware('permission:view_driver_reports')->group(function () {
            Route::get('/reports/drivers-summary', [ReportController::class, 'driversSummary']);
            Route::get('/reports/ratings-distribution', [ReportController::class, 'ratingsDistribution']);
            Route::get('/reports/export/drivers', [ReportController::class, 'exportDriversReport']);
        });

        Route::middleware('permission:view_route_reports')->group(function () {
            Route::get('/reports/destinations-summary', [ReportController::class, 'destinationsSummary']);
            Route::get('/reports/hourly-summary', [ReportController::class, 'hourlySummary']);
            Route::get('/reports/daily-summary', [ReportController::class, 'dailySummary']);
            Route::get('/reports/routes-performance', [ReportController::class, 'routesPerformance']);
            Route::get('/reports/routes-details', [ReportController::class, 'routesDetailsSummary']);
            Route::get('/reports/trips-coordinates', [ReportController::class, 'tripsCoordinates']);
            Route::get('/reports/export/routes', [ReportController::class, 'exportRoutesReport']);
        });

        Route::middleware('permission:view_passenger_reports')->group(function () {
            Route::get('/reports/passengers-summary', [ReportController::class, 'passengersSummary']);
            Route::get('/reports/export/passengers', [ReportController::class, 'exportPassengersReport']);
        });

        Route::middleware('permission:manage_users')->group(function () {
            Route::get('/users', [UserController::class, 'listUsers']);
            Route::post('/users/drivers', [UserController::class, 'storeDriver']);
            Route::patch('/users/{id}/toggle-status', [UserController::class, 'toggleStatus']);
            Route::put('/users/{id}', [UserController::class, 'updateUser']);
            Route::delete('/users/{id}', [UserController::class, 'deleteUser']);
        });

        Route::middleware('permission:manage_admins')->group(function () {
            Route::post('/users/admins', [UserController::class, 'storeAdmin']);
        });

        Route::middleware('permission:manage_vehicles')->group(function () {
            Route::apiResource('vehicles', VehicleController::class);
        });

        Route::middleware('permission:manage_destinations')->group(function () {
            Route::post('/destinations', [DestinationController::class, 'store']);
            Route::put('/destinations/{id}', [DestinationController::class, 'update']);
            Route::delete('/destinations/{id}', [DestinationController::class, 'destroy']);
            Route::patch('/destinations/{id}/toggle-status', [DestinationController::class, 'toggleStatus']);
        });

        // Rutas Exclusivas para el Administrador (Configuraciones base)
        Route::middleware('role:admin')->group(function () {
            Route::apiResource('rols', RolController::class);
            Route::apiResource('states', StateController::class);
        });

        Route::post('/trips/request', [TripController::class, 'request']);
        Route::post('/trips/{id}/accept', [TripController::class, 'accept']);
        Route::post('/trips/{id}/start', [TripController::class, 'start']);
        Route::post('/trips/{tripId}/board/{passengerId}', [TripController::class, 'boardPassenger']);
        Route::post('/trips/{tripId}/dropoff/{passengerId}', [TripController::class, 'dropOffPassenger']);
        Route::post('/trips/{tripId}/cancel-passenger/{passengerId}', [TripController::class, 'cancelPassenger']);
        Route::post('/trips/{id}/finish', [TripController::class, 'finish']);
        Route::delete('/trips/{id}/cancel', [TripController::class, 'cancel']);
        Route::post('/trips/{id}/position', [TripPositionController::class, 'store']);
        Route::post('/trips/{id}/rate', [TripRatingController::class, 'store']);
        Route::get('/ratings', [TripRatingController::class, 'index']);

        Route::middleware('permission:view_history')->group(function () {
            Route::get('/trips', [TripController::class, 'index']); // Historial Admin Completo
        });

        Route::get('/trips/history', [TripController::class, 'history']); // Historial personal (Pasajeros/Conductores)

        Route::middleware('role:admin')->group(function () {
            Route::get('/admin/disconnect-requests', [DriverLocationController::class, 'getAllDisconnectRequests']);
            Route::post('/admin/driver/{id}/approve-disconnect', [DriverLocationController::class, 'approveDisconnect']);
            Route::post('/admin/driver/{id}/reject-disconnect', [DriverLocationController::class, 'rejectDisconnect']);
        });

        Route::middleware('role:conductor')->group(function () {
            Route::post('/driver/location', [DriverLocationController::class, 'updateDriverLocation']);
            Route::post('/driver/offline', [DriverLocationController::class, 'setDriverOffline']);
            Route::post('/driver/request-disconnect', [DriverLocationController::class, 'requestDisconnect']);
        });
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
