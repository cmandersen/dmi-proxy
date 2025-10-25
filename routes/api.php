<?php

use App\Http\Controllers\Api\V1\WeatherController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->group(function () {
    Route::prefix('weather')->name('weather.')->controller(WeatherController::class)->group(function () {
        Route::get('current/{location}', 'current')->name('current');
        Route::get('forecast/{location}', 'forecast')->name('forecast');
        Route::get('historical/{location}', 'historical')->name('historical');
    });
});
