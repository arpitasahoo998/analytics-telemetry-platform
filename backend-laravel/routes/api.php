<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventIngestionController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DishController;

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'analytics-laravel-api',
        'framework' => 'Laravel ' . app()->version(),
        'database' => 'mysql',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Event Ingestion API
Route::post('/v1/events', [EventIngestionController::class, 'ingest']);

// Analytics Query Endpoints
Route::prefix('/v1/analytics')->group(function () {
    Route::get('/dashboard', [AnalyticsController::class, 'dashboard']);
    Route::get('/overview', [AnalyticsController::class, 'overview']);
    Route::get('/timeseries', [AnalyticsController::class, 'timeseries']);
    Route::get('/popular-pages', [AnalyticsController::class, 'popularPages']);
    Route::get('/event-breakdown', [AnalyticsController::class, 'eventBreakdown']);
    Route::get('/funnel', [AnalyticsController::class, 'funnel']);
    Route::get('/recent-events', [AnalyticsController::class, 'recentEvents']);
});

// Restaurant Menu Dishes (backed dynamically by MySQL)
Route::get('/dishes', [DishController::class, 'index']);
Route::get('/dishes/{dishId}', [DishController::class, 'show']);
Route::post('/dishes', [DishController::class, 'store']);
Route::put('/dishes/{dishId}', [DishController::class, 'update']);
Route::delete('/dishes/{dishId}', [DishController::class, 'destroy']);

// Restaurant Orders
Route::get('/orders', [OrderController::class, 'index']);
Route::get('/orders/{orderId}', [OrderController::class, 'show']);
Route::post('/orders', [OrderController::class, 'store']);
Route::patch('/orders/{orderId}/status', [OrderController::class, 'updateStatus']);

