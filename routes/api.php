<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RolController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\TabsController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\TripPositionController;
use App\Http\Controllers\TripRatingController;
use App\Http\Controllers\AuthController;

// Public
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

        Route::post('/trips/request', [TripController::class, 'request']);
        Route::post('/trips/{id}/accept', [TripController::class, 'accept']);
        Route::post('/trips/{id}/finish', [TripController::class, 'finish']);
        Route::post('/trips/{id}/position', [TripPositionController::class, 'store']);
        Route::post('/trips/{id}/rate', [TripRatingController::class, 'store']);
    });
});

