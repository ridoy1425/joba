<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PeriodCalculationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;

Route::prefix('auth')->group(function () {
    Route::get('google', [AuthController::class, 'redirectToGoogle']);
    Route::get('google/callback', [AuthController::class, 'handleGoogleCallback']);
});



// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::put('/profile', [AuthController::class, 'updateProfile']);

    Route::prefix('period')->group(function () {

        // Main calculation endpoint
        Route::post('/calculate', [PeriodCalculationController::class, 'calculate']);

        // Calendar view endpoint
        Route::post('/calendar', [PeriodCalculationController::class, 'calendar'])
            ->name('period.calendar');

        // History endpoints (optional - for authenticated users)
        Route::get('/history', [PeriodCalculationController::class, 'history'])
            ->name('period.history');

        Route::get('/history/{id}', [PeriodCalculationController::class, 'show'])
            ->name('period.show');

        Route::delete('/history/{id}', [PeriodCalculationController::class, 'destroy'])
            ->name('period.destroy');
    });
});