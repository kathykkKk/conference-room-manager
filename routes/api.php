<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DebugController;

// Все роуты здесь автоматически имеют префикс /api
Route::get('/test-db', [DebugController::class, 'index']);


use App\Http\Controllers\Api\HallController;
use App\Http\Controllers\Api\BookingController;

// Публичные роуты (для клиентов)
Route::prefix('v1')->group(function () {
    // Получение данных для фильтров (парки, оборудование и т.д.)
    Route::get('/halls/filters', [HallController::class, 'getFilters']);
    
    // Поиск залов с фильтрацией
    Route::get('/halls', [HallController::class, 'index']);
    
    // Детальная карточка зала
    Route::get('/halls/{hall}', [HallController::class, 'show']);

    // Создание бронирования
    Route::post('/bookings', [BookingController::class, 'store']);
});

use App\Http\Controllers\Api\Manager\BookingManagementController;

// Роуты для менеджера (в будущем здесь будет middleware 'auth')
Route::prefix('v1/manager')->group(function () {
    Route::get('/bookings', [BookingManagementController::class, 'index']);
    Route::post('/bookings/{booking}/confirm', [BookingManagementController::class, 'confirm']);
    Route::post('/bookings/{booking}/cancel', [BookingManagementController::class, 'cancel']);
});

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Manager\ReportController;
use App\Http\Controllers\Api\Manager\HallManagementController;

// Публичный вход
Route::post('/v1/login', [AuthController::class, 'login']);

// Защищенные роуты для менеджера
Route::middleware('auth:sanctum')->prefix('v1/manager')->group(function () {
    
    Route::get('/bookings', [BookingManagementController::class, 'index']);
    Route::post('/bookings/{booking}/confirm', [BookingManagementController::class, 'confirm']);
    Route::post('/bookings/{booking}/cancel', [BookingManagementController::class, 'cancel']);
    
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/reports/income', [ReportController::class, 'incomeReport']);

    Route::get('/halls', [HallManagementController::class, 'index']);
    Route::post('/halls', [HallManagementController::class, 'store']);
    Route::put('/halls/{hall}', [HallManagementController::class, 'update']);
    Route::delete('/halls/{hall}', [HallManagementController::class, 'destroy']);
});