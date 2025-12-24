<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RolController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\TabsController;
use App\Http\Controllers\CareerController;
use App\Http\Controllers\CareerPositionController;
use App\Http\Controllers\CareerRatingController;
use App\Http\Controllers\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Auth Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas por autenticación
Route::middleware('auth:sanctum')->group(function () {

    // Roles
    Route::apiResource('rols', RolController::class);

    // States
    Route::apiResource('states', StateController::class);

    // Tabs
    Route::apiResource('tabs', TabsController::class);

    // Careers
    Route::post('/careers/request', [CareerController::class, 'request']);
    Route::post('/careers/{id}/accept', [CareerController::class, 'accept']);
    Route::post('/careers/{id}/finish', [CareerController::class, 'finish']);

    // Career Positions
    Route::post('/careers/{id}/position', [CareerPositionController::class, 'store']);

    // Career Ratings
    Route::post('/careers/{id}/rate', [CareerRatingController::class, 'store']);
});
