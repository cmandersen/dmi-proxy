<?php

namespace App\Providers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

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
                ->baseUrl('https://dmigw.govcloud.dk/v2/climateData')
                ->timeout(10)
                ->retry(3, 100, function ($exception) {
                    return $exception instanceof ConnectionException;
                });
        });

        Http::macro('dmiMetObs', function () {
            return Http::withHeaders([
                'X-Gravitee-Api-Key' => config('services.dmi.metobs_key'),
                'Accept' => 'application/geo+json',
            ])
                ->baseUrl('https://dmigw.govcloud.dk/v2/metObs')
                ->timeout(10)
                ->retry(3, 100);
        });

        Http::macro('dmiForecast', function () {
            return Http::withHeaders([
                'X-Gravitee-Api-Key' => config('services.dmi.forecast_key'),
            ])
                ->baseUrl('https://dmigw.govcloud.dk/v1/forecastedr')
                ->timeout(15)
                ->retry(3, 200);
        });
    }
}
