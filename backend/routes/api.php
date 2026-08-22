<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientAppointmentController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/client/register', [AuthController::class, 'registerClient']);
Route::get('/services', [ServiceController::class, 'index']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('staff')->group(function (): void {
        Route::apiResource('appointments', AppointmentController::class);
        Route::apiResource('clients', ClientController::class)->except(['destroy']);
        Route::patch('/clients/{client}/deactivate', [ClientController::class, 'deactivate']);
        Route::apiResource('services', ServiceController::class)->except(['destroy', 'index']);
        Route::get('/management/services', [ServiceController::class, 'index']);
        Route::patch('/services/{service}/deactivate', [ServiceController::class, 'deactivate']);
    });

    Route::middleware('client')->group(function (): void {
        Route::post('/booking-requests', [ClientAppointmentController::class, 'store']);
        Route::get('/client/appointments', [ClientAppointmentController::class, 'index']);
        Route::patch('/client/appointments/{appointment}/cancel', [ClientAppointmentController::class, 'cancel']);
    });
});
