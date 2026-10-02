<?php

namespace App\Providers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Http::macro('dmiClimate', function () {
            return Http::withHeaders([
                'X-Gravitee-Api-Key' => config('services.dmi.climate_key'),
                'Accept' => 'application/geo+json',
            ])
                ->baseUrl(config('services.dmi.base_url').'/v2/climateData')
                ->timeout(10)
                ->retry(3, 100, function ($exception) {
                    return $exception instanceof ConnectionException;
                }, throw: false);
        });

        Http::macro('dmiMetObs', function () {
            return Http::withHeaders([
                'X-Gravitee-Api-Key' => config('services.dmi.metobs_key'),
                'Accept' => 'application/geo+json',
            ])
                ->baseUrl(config('services.dmi.base_url').'/v2/metObs')
                ->timeout(10)
                ->retry(3, 100, throw: false);
        });

        Http::macro('dmiForecast', function () {
            return Http::withHeaders([
                'X-Gravitee-Api-Key' => config('services.dmi.forecast_key'),
            ])
                ->baseUrl(config('services.dmi.base_url').'/v1/forecastedr')
                ->timeout(30)
                ->retry([500, 1500], when: function (Throwable $exception): bool {
                    return $exception instanceof ConnectionException
                        || ($exception instanceof RequestException && $exception->response->serverError());
                }, throw: false);
        });
    }
}
