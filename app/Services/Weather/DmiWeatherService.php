<?php

namespace App\Services\Weather;

use App\DTOs\CoordinatesData;
use App\DTOs\ForecastDataDTO;
use App\DTOs\HistoricalWeatherDTO;
use App\DTOs\LocationData;
use App\DTOs\PeriodData;
use App\DTOs\WeatherDataDTO;
use App\Exceptions\WeatherServiceException;
use App\Services\Location\GeocodingService;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class DmiWeatherService
{
    public function __construct(
        private WeatherTransformer $transformer,
        private GeocodingService $geocoding,
    ) {}

    public function getCurrentWeather(string $location, array $parameters = []): WeatherDataDTO
    {
        $coords = $this->geocoding->geocode($location);
        $cacheKey = "weather:current:{$coords['lat']}:{$coords['lon']}";

        return Cache::remember($cacheKey, config('services.dmi.cache_ttl.current'),
            function () use ($coords, $parameters) {
                // Find nearest station
                $station = $this->findNearestStation($coords['lat'], $coords['lon']);

                // Map user-friendly parameter names to DMI parameter IDs
                $parameterIds = empty($parameters)
                    ? ['temp_dry', 'humidity_past1h', 'wind_speed_past1h', 'wind_dir', 'pressure_at_sea']
                    : $this->mapCurrentWeatherParameters($parameters);

                $responses = $this->fetchMultipleParameters($station['id'], $parameterIds);

                return $this->transformer->toWeatherDTO($responses, $station);
            }
        );
    }

    public function getForecast(string $location, int $hours = 48): ForecastDataDTO
    {
        $coords = $this->geocoding->geocode($location);
        $cacheKey = "weather:forecast:{$coords['lat']}:{$coords['lon']}:{$hours}";

        return Cache::remember($cacheKey, config('services.dmi.cache_ttl.forecast'),
            function () use ($coords, $location, $hours) {
                $response = Http::dmiForecast()->get(
                    '/collections/harmonie_dini_sf/position',
                    [
                        'coords' => "POINT({$coords['lon']} {$coords['lat']})",
                        'parameter-name' => 'temperature-2m,wind-speed-10m,wind-dir-10m,total-precipitation,fraction-of-cloud-cover',
                    ]
                );

                if ($response->failed()) {
                    throw new WeatherServiceException(
                        'Failed to fetch forecast data',
                        $response->status()
                    );
                }

                $forecastData = $this->transformer->transformForecast($response->json());

                // Limit to requested hours
                $limitedForecast = array_slice($forecastData, 0, $hours);

                return new ForecastDataDTO(
                    location: new LocationData(
                        name: $location,
                        coordinates: new CoordinatesData(
                            lat: $coords['lat'],
                            lon: $coords['lon'],
                        ),
                    ),
                    generated_at: now(),
                    forecast: $limitedForecast,
                );
            }
        );
    }

    public function getHistoricalWeather(
        string $location,
        string $from,
        string $to,
        string $timeResolution = 'day',
        array $parameters = []
    ): HistoricalWeatherDTO {
        $coords = $this->geocoding->geocode($location);
        $cacheKey = "weather:historical:{$coords['lat']}:{$coords['lon']}:{$from}:{$to}:{$timeResolution}";

        return Cache::remember($cacheKey, config('services.dmi.cache_ttl.historical', 86400),
            function () use ($coords, $location, $from, $to, $timeResolution, $parameters) {
                // Find nearest station
                $station = $this->findNearestStation($coords['lat'], $coords['lon']);

                // Map user-friendly parameter names to DMI parameter IDs
                $parameterIds = empty($parameters)
                    ? ['mean_temp', 'acc_precip', 'mean_wind_speed']
                    : $this->mapHistoricalParameters($parameters);

                // Fetch climate data for multiple parameters
                $responses = $this->fetchHistoricalParameters(
                    $station['id'],
                    $parameterIds,
                    $from,
                    $to,
                    $timeResolution
                );

                $historicalData = $this->transformer->transformHistorical($responses, $timeResolution);

                return new HistoricalWeatherDTO(
                    location: new LocationData(
                        name: $location,
                        coordinates: new CoordinatesData(
                            lat: $station['lat'],
                            lon: $station['lon'],
                        ),
                        station: $station['name'],
                    ),
                    period: new PeriodData(
                        from: Carbon::parse($from),
                        to: Carbon::parse($to),
                        resolution: $timeResolution,
                    ),
                    data: $historicalData,
                );
            }
        );
    }

    private function fetchHistoricalParameters(
        string $stationId,
        array $parameterIds,
        string $from,
        string $to,
        string $timeResolution
    ): array {
        // Convert dates to RFC3339 format for DMI Climate API
        $fromRfc3339 = Carbon::parse($from)->toRfc3339String();
        $toRfc3339 = Carbon::parse($to)->toRfc3339String();

        // DMI Climate API: one parameter per request
        // Use Http::pool for concurrent requests
        $responses = Http::pool(function ($pool) use ($stationId, $parameterIds, $fromRfc3339, $toRfc3339, $timeResolution) {
            return collect($parameterIds)->map(function ($parameterId) use ($pool, $stationId, $fromRfc3339, $toRfc3339, $timeResolution) {
                return $pool->as($parameterId)
                    ->withHeaders([
                        'X-Gravitee-Api-Key' => config('services.dmi.climate_key'),
                        'Accept' => 'application/geo+json',
                    ])
                    ->baseUrl('https://dmigw.govcloud.dk/v2/climateData')
                    ->timeout(30)
                    ->retry(3, 200)
                    ->get(
                        '/collections/stationValue/items',
                        [
                            'stationId' => $stationId,
                            'parameterId' => $parameterId,
                            'timeResolution' => $timeResolution,
                            'datetime' => "{$fromRfc3339}/{$toRfc3339}",
                            'limit' => 10000,
                        ]
                    );
            })->all();
        });

        return collect($responses)
            ->filter(fn ($response) => $response instanceof Response && $response->successful())
            ->mapWithKeys(function ($response, $parameterId) {
                $features = $response->json('features', []);

                return [$parameterId => $features];
            })
            ->all();
    }

    private function findNearestStation(float $lat, float $lon): array
    {
        $bbox = $this->createBoundingBox($lat, $lon, 50); // 50km radius

        $response = Http::dmiMetObs()->get('/collections/station/items', [
            'status' => 'Active',
            'bbox' => "{$bbox['minLon']},{$bbox['minLat']},{$bbox['maxLon']},{$bbox['maxLat']}",
            'datetime' => now()->toIso8601String().'/..',
            'limit' => 10,
        ]);

        if ($response->failed()) {
            throw new WeatherServiceException(
                'Failed to fetch stations',
                $response->status()
            );
        }

        $stations = $response->json('features', []);

        if (empty($stations)) {
            throw new WeatherServiceException('No active stations found nearby', 404);
        }

        return $this->selectClosestStation($stations, $lat, $lon);
    }

    private function fetchMultipleParameters(string $stationId, array $parameterIds): array
    {
        // DMI API limitation: one parameter per request
        // Use Http::pool for concurrent requests
        $responses = Http::pool(function ($pool) use ($stationId, $parameterIds) {
            return collect($parameterIds)->map(function ($parameterId) use ($pool, $stationId) {
                return $pool->as($parameterId)
                    ->withHeaders([
                        'X-Gravitee-Api-Key' => config('services.dmi.metobs_key'),
                        'Accept' => 'application/geo+json',
                    ])
                    ->baseUrl('https://dmigw.govcloud.dk/v2/metObs')
                    ->timeout(10)
                    ->retry(3, 100)
                    ->get(
                        '/collections/observation/items',
                        [
                            'stationId' => $stationId,
                            'parameterId' => $parameterId,
                            'period' => 'latest',
                            'limit' => 1,
                        ]
                    );
            })->all();
        });

        return collect($responses)
            ->filter(fn ($response) => $response instanceof Response && $response->successful())
            ->mapWithKeys(function ($response, $parameterId) {
                $features = $response->json('features', []);
                if (empty($features)) {
                    return [$parameterId => null];
                }

                return [$parameterId => $features[0]['properties']];
            })
            ->all();
    }

    private function createBoundingBox(float $lat, float $lon, int $radiusKm): array
    {
        // Approximate degrees for given radius
        $latDelta = $radiusKm / 111.0; // 1 degree latitude ≈ 111km
        $lonDelta = $radiusKm / (111.0 * cos(deg2rad($lat)));

        return [
            'minLat' => $lat - $latDelta,
            'maxLat' => $lat + $latDelta,
            'minLon' => $lon - $lonDelta,
            'maxLon' => $lon + $lonDelta,
        ];
    }

    private function selectClosestStation(array $stations, float $lat, float $lon): array
    {
        return collect($stations)
            ->map(function ($station) use ($lat, $lon) {
                [$sLon, $sLat] = $station['geometry']['coordinates'];
                $distance = $this->haversineDistance($lat, $lon, $sLat, $sLon);

                return [
                    'id' => $station['properties']['stationId'],
                    'name' => $station['properties']['name'],
                    'distance' => $distance,
                    'lat' => $sLat,
                    'lon' => $sLon,
                ];
            })
            ->sortBy('distance')
            ->first();
    }

    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function mapCurrentWeatherParameters(array $requested): array
    {
        $mapping = [
            'temperature' => ['temp_dry'],
            'humidity' => ['humidity_past1h'],
            'wind' => ['wind_speed_past1h', 'wind_dir'],
            'pressure' => ['pressure_at_sea'],
        ];

        return collect($requested)
            ->flatMap(fn ($param) => $mapping[$param] ?? [])
            ->filter()
            ->values()
            ->all();
    }

    private function mapHistoricalParameters(array $requested): array
    {
        $mapping = [
            'temperature' => ['mean_temp'],
            'precipitation' => ['acc_precip'],
            'wind' => ['mean_wind_speed', 'mean_wind_dir'],
            'humidity' => ['mean_relative_hum'],
            'sunshine' => ['bright_sunshine'],
        ];

        return collect($requested)
            ->flatMap(fn ($param) => $mapping[$param] ?? [])
            ->filter()
            ->values()
            ->all();
    }
}
