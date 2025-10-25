<?php

namespace App\Services\Weather;

use App\Actions\Weather\CalculateWindChill;
use App\DTOs\CoordinatesData;
use App\DTOs\LocationData;
use App\DTOs\PressureData;
use App\DTOs\TemperatureData;
use App\DTOs\WeatherDataDTO;
use App\DTOs\WindData;
use Carbon\Carbon;
use Spatie\LaravelData\Optional;

class WeatherTransformer
{
    private const PARAMETER_MAPPING = [
        'temp_dry' => 'temperature',
        'mean_temp' => 'temperature',
        'humidity' => 'humidity',
        'humidity_past1h' => 'humidity',
        'wind_speed_past1h' => 'windSpeed',
        'mean_wind_speed' => 'windSpeed',
        'wind_dir' => 'windDirection',
        'mean_wind_dir' => 'windDirection',
        'pressure_at_sea' => 'pressure',
    ];

    public function toWeatherDTO(array $parameterData, array $station): WeatherDataDTO
    {
        $normalized = $this->normalizeParameters($parameterData);
        $timestamp = $this->extractTimestamp($parameterData);

        // Calculate wind chill
        $windChill = app(CalculateWindChill::class)->calculate(
            $normalized['temperature'] ?? null,
            $normalized['windSpeed'] ?? null
        );

        return new WeatherDataDTO(
            location: new LocationData(
                name: $station['name'],
                coordinates: new CoordinatesData(
                    lat: $station['lat'],
                    lon: $station['lon'],
                ),
                station: $station['name'],
            ),
            timestamp: $timestamp,
            temperature: new TemperatureData(
                value: $normalized['temperature'] ?? null,
            ),
            humidity: $normalized['humidity'] ?? null,
            wind: new WindData(
                speed: $normalized['windSpeed'] ?? null,
                direction: $normalized['windDirection'] ?? null,
                chill: $windChill ?? Optional::create(),
                chill_unit: $windChill !== null ? 'celsius' : Optional::create(),
            ),
            pressure: new PressureData(
                value: $normalized['pressure'] ?? null,
            ),
        );
    }

    private function normalizeParameters(array $parameterData): array
    {
        $normalized = [];

        foreach ($parameterData as $parameterId => $data) {
            if ($data === null) {
                continue;
            }

            $mappedName = self::PARAMETER_MAPPING[$parameterId] ?? null;
            if ($mappedName) {
                $normalized[$mappedName] = $data['value'] ?? null;
            }
        }

        return $normalized;
    }

    private function extractTimestamp(array $parameterData): Carbon
    {
        foreach ($parameterData as $data) {
            if ($data && isset($data['observed'])) {
                return Carbon::parse($data['observed']);
            }
            if ($data && isset($data['from'])) {
                return Carbon::parse($data['from']);
            }
        }

        return now();
    }

    public function transformForecast(array $forecastResponse): array
    {
        // CoverageJSON format has timestamps and values in separate arrays
        $timestamps = $forecastResponse['domain']['axes']['t']['values'] ?? [];
        $ranges = $forecastResponse['ranges'] ?? [];

        $temperatureValues = $ranges['temperature-2m']['values'] ?? [];
        $windSpeedValues = $ranges['wind-speed-10m']['values'] ?? [];
        $windDirValues = $ranges['wind-dir-10m']['values'] ?? [];
        $precipitationValues = $ranges['total-precipitation']['values'] ?? [];
        $cloudCoverValues = $ranges['fraction-of-cloud-cover']['values'] ?? [];

        // Zip timestamps with all parameter values, filtering for future timestamps only
        return collect($timestamps)->map(function ($timestamp, $index) use (
            $temperatureValues,
            $windSpeedValues,
            $windDirValues,
            $precipitationValues,
            $cloudCoverValues
        ) {
            $temperature = $this->kelvinToCelsius($temperatureValues[$index] ?? null);
            $windSpeed = $windSpeedValues[$index] ?? null;
            $windChill = app(CalculateWindChill::class)->calculate($temperature, $windSpeed);

            $dataPoint = [
                'timestamp' => $timestamp,
                'temperature' => $temperature,
                'temperature_unit' => 'celsius',
                'wind_speed' => $windSpeed,
                'wind_direction' => $windDirValues[$index] ?? null,
                'wind_speed_unit' => 'meters_per_second',
                'precipitation' => $precipitationValues[$index] ?? null,
                'precipitation_unit' => 'mm',
                'cloud_cover' => $cloudCoverValues[$index] ?? null,
                'cloud_cover_unit' => 'percent',
            ];

            if ($windChill !== null) {
                $dataPoint['wind_chill'] = $windChill;
                $dataPoint['wind_chill_unit'] = 'celsius';
            }

            return $dataPoint;
        })
        ->filter(fn ($forecast) => Carbon::parse($forecast['timestamp'])->isFuture())
        ->values()
        ->all();
    }

    private function kelvinToCelsius(?float $kelvin): ?float
    {
        return $kelvin !== null ? round($kelvin - 273.15, 1) : null;
    }

    public function transformHistorical(array $parameterResponses, string $timeResolution): array
    {
        // Collect all timestamps across all parameters
        $allTimestamps = [];

        foreach ($parameterResponses as $features) {
            foreach ($features as $feature) {
                $from = $feature['properties']['from'] ?? null;
                if ($from && ! in_array($from, $allTimestamps)) {
                    $allTimestamps[] = $from;
                }
            }
        }

        sort($allTimestamps);

        // Build time-series data with all parameters
        return collect($allTimestamps)->map(function ($timestamp) use ($parameterResponses) {
            $dataPoint = [
                'timestamp' => $timestamp,
                'timestamp_unit' => 'iso8601',
            ];

            // Add each parameter's value for this timestamp
            foreach ($parameterResponses as $parameterId => $features) {
                $value = $this->findValueForTimestamp($features, $timestamp);

                // Map to friendly names
                $friendlyName = $this->mapHistoricalParameter($parameterId);
                if ($value !== null && $friendlyName) {
                    $dataPoint[$friendlyName] = [
                        'value' => $value['value'],
                        'unit' => $value['unit'] ?? $this->getUnitForParameter($parameterId),
                    ];
                }
            }

            // Calculate wind chill if we have both temperature and wind speed
            $temperature = $dataPoint['temperature']['value'] ?? null;
            $windSpeed = $dataPoint['wind_speed']['value'] ?? null;

            if ($temperature !== null && $windSpeed !== null) {
                $windChill = app(CalculateWindChill::class)->calculate($temperature, $windSpeed);
                if ($windChill !== null) {
                    $dataPoint['wind_chill'] = [
                        'value' => $windChill,
                        'unit' => 'celsius',
                    ];
                }
            }

            return $dataPoint;
        })->all();
    }

    private function findValueForTimestamp(array $features, string $timestamp): ?array
    {
        foreach ($features as $feature) {
            if (($feature['properties']['from'] ?? null) === $timestamp) {
                return [
                    'value' => $feature['properties']['value'] ?? null,
                    'unit' => $feature['properties']['unit'] ?? null,
                ];
            }
        }
        return null;
    }

    private function mapHistoricalParameter(string $parameterId): ?string
    {
        $mapping = [
            'mean_temp' => 'temperature',
            'min_temp' => 'temperature_min',
            'max_temp' => 'temperature_max',
            'acc_precip' => 'precipitation',
            'mean_wind_speed' => 'wind_speed',
            'max_wind_speed_3sec' => 'wind_speed_max',
            'mean_wind_dir' => 'wind_direction',
            'mean_cloud_cover' => 'cloud_cover',
            'bright_sunshine' => 'sunshine_hours',
            'mean_relative_hum' => 'humidity',
        ];

        return $mapping[$parameterId] ?? $parameterId;
    }

    private function getUnitForParameter(string $parameterId): string
    {
        $units = [
            'mean_temp' => 'celsius',
            'min_temp' => 'celsius',
            'max_temp' => 'celsius',
            'acc_precip' => 'millimeters',
            'mean_wind_speed' => 'meters_per_second',
            'max_wind_speed_3sec' => 'meters_per_second',
            'mean_wind_dir' => 'degrees',
            'mean_cloud_cover' => 'percent',
            'bright_sunshine' => 'hours',
            'mean_relative_hum' => 'percent',
        ];

        return $units[$parameterId] ?? 'unknown';
    }
}
