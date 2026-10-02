<?php

use App\Exceptions\WeatherServiceException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api')
                ->name('api.')
                ->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (WeatherServiceException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => $e->getMessage(),
                    'location' => $request->route('location'),
                ], $e->getCode() >= 400 && $e->getCode() <= 599 ? $e->getCode() : 500);
            }
        });

        $exceptions->render(function (ConnectionException|RequestException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => 'Weather service temporarily unavailable',
                    'location' => $request->route('location'),
                ], 503, ['Retry-After' => '60']);
            }
        });
    })->create();
