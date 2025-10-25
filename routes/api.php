<?php

use App\Http\Controllers\Api\V1\WeatherController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Current weather
    Route::get('/weather/current/{location}', [WeatherController::class, 'current']);

    // Forecast
    Route::get('/weather/forecast/{location}', [WeatherController::class, 'forecast']);

    // Historical weather
    Route::get('/weather/historical/{location}', [WeatherController::class, 'historical']);
});
