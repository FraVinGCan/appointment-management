<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientAppointmentController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/client/register', [AuthController::class, 'registerClient']);
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{service}', [ServiceController::class, 'show']);
Route::get('/user', [AuthController::class, 'user']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('admin')->group(function (): void {
        Route::apiResource('appointments', AppointmentController::class);
        Route::post('/appointments/{appointment}/confirm', [AppointmentController::class, 'confirm']);
        Route::post('/appointments/{appointment}/complete', [AppointmentController::class, 'complete']);
        Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel']);
        Route::apiResource('clients', ClientController::class)->except(['destroy']);
        Route::patch('/clients/{client}/deactivate', [ClientController::class, 'deactivate']);
        Route::patch('/clients/{client}/activate', [ClientController::class, 'activate']);
        Route::apiResource('services', ServiceController::class)->except(['destroy', 'index', 'show']);
        Route::get('/management/services', [ServiceController::class, 'index']);
        Route::get('/management/services/categories', [ServiceController::class, 'categories']);
        Route::patch('/services/{service}/deactivate', [ServiceController::class, 'deactivate']);
        Route::get('/dashboard/stats', [DashboardController::class, 'index']);
    });

    Route::middleware('client')->group(function (): void {
        Route::post('/booking-requests', [ClientAppointmentController::class, 'store']);
        Route::get('/client/appointments', [ClientAppointmentController::class, 'index']);
        Route::patch('/client/appointments/{appointment}/cancel', [ClientAppointmentController::class, 'cancel']);
    });
});
