<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CurrentWeatherRequest;
use App\Http\Requests\Api\ForecastRequest;
use App\Http\Requests\Api\HistoricalWeatherRequest;
use App\Services\Weather\DmiWeatherService;
use Illuminate\Http\JsonResponse;

class WeatherController extends Controller
{
    public function __construct(
        private DmiWeatherService $weatherService
    ) {}

    public function current(CurrentWeatherRequest $request, string $location): JsonResponse
    {
        $weather = $this->weatherService->getCurrentWeather(
            $location,
            $request->input('parameters')
        );

        return response()->json($weather)
            ->header('Cache-Control', 'public, max-age=300');
    }

    public function forecast(ForecastRequest $request, string $location): JsonResponse
    {
        $forecast = $this->weatherService->getForecast(
            $location,
            $request->input('hours')
        );

        return response()->json($forecast)
            ->header('Cache-Control', 'public, max-age=1800');
    }

    public function historical(HistoricalWeatherRequest $request, string $location): JsonResponse
    {
        $historical = $this->weatherService->getHistoricalWeather(
            $location,
            $request->input('from'),
            $request->input('to'),
            $request->input('resolution'),
            $request->input('parameters')
        );

        return response()->json($historical)
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
