<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CurrentWeatherRequest;
use App\Http\Requests\Api\V1\ForecastRequest;
use App\Http\Requests\Api\V1\HistoricalWeatherRequest;
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
