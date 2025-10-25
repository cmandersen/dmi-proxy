<?php

namespace Tests\Unit\Services\Weather;

use App\Services\Weather\WeatherTransformer;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class WeatherTransformerTest extends TestCase
{
    private WeatherTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new WeatherTransformer();
    }

    public function test_transforms_weather_dto_with_all_parameters(): void
    {
        $parameterData = [
            'temp_dry' => [
                'value' => 15.5,
                'observed' => '2024-01-15T12:00:00Z',
            ],
            'humidity_past1h' => [
                'value' => 65,
                'observed' => '2024-01-15T12:00:00Z',
            ],
            'wind_speed_past1h' => [
                'value' => 5.2,
                'observed' => '2024-01-15T12:00:00Z',
            ],
            'wind_dir' => [
                'value' => 180,
                'observed' => '2024-01-15T12:00:00Z',
            ],
            'pressure_at_sea' => [
                'value' => 1013.25,
                'observed' => '2024-01-15T12:00:00Z',
            ],
        ];

        $station = [
            'id' => '06180',
            'name' => 'Copenhagen',
            'lat' => 55.6761,
            'lon' => 12.5683,
        ];

        $result = $this->transformer->toWeatherDTO($parameterData, $station);

        $this->assertEquals('Copenhagen', $result->location->name);
        $this->assertEquals(55.6761, $result->location->coordinates->lat);
        $this->assertEquals(12.5683, $result->location->coordinates->lon);
        $this->assertEquals('Copenhagen', $result->location->station);
        $this->assertEquals(15.5, $result->temperature->value);
        $this->assertEquals(65, $result->humidity);
        $this->assertEquals(5.2, $result->wind->speed);
        $this->assertEquals(180, $result->wind->direction);
        $this->assertEquals(1013.25, $result->pressure->value);
    }

    public function test_transforms_weather_dto_with_partial_parameters(): void
    {
        $parameterData = [
            'temp_dry' => [
                'value' => 10.0,
                'observed' => '2024-01-15T12:00:00Z',
            ],
        ];

        $station = [
            'id' => '06180',
            'name' => 'Copenhagen',
            'lat' => 55.6761,
            'lon' => 12.5683,
        ];

        $result = $this->transformer->toWeatherDTO($parameterData, $station);

        $this->assertEquals(10.0, $result->temperature->value);
        $this->assertNull($result->humidity);
        $this->assertNull($result->wind->speed);
        $this->assertNull($result->wind->direction);
        $this->assertNull($result->pressure->value);
    }

    public function test_transforms_weather_dto_handles_null_values(): void
    {
        $parameterData = [
            'temp_dry' => null,
            'humidity_past1h' => null,
        ];

        $station = [
            'id' => '06180',
            'name' => 'Copenhagen',
            'lat' => 55.6761,
            'lon' => 12.5683,
        ];

        $result = $this->transformer->toWeatherDTO($parameterData, $station);

        $this->assertNull($result->temperature->value);
        $this->assertNull($result->humidity);
    }

    public function test_extracts_timestamp_from_observed_field(): void
    {
        $parameterData = [
            'temp_dry' => [
                'value' => 15.5,
                'observed' => '2024-01-15T12:00:00Z',
            ],
        ];

        $station = [
            'id' => '06180',
            'name' => 'Copenhagen',
            'lat' => 55.6761,
            'lon' => 12.5683,
        ];

        $result = $this->transformer->toWeatherDTO($parameterData, $station);

        $this->assertEquals('2024-01-15 12:00:00', $result->timestamp->format('Y-m-d H:i:s'));
    }

    public function test_extracts_timestamp_from_from_field(): void
    {
        $parameterData = [
            'mean_temp' => [
                'value' => 15.5,
                'from' => '2024-01-15T00:00:00Z',
            ],
        ];

        $station = [
            'id' => '06180',
            'name' => 'Copenhagen',
            'lat' => 55.6761,
            'lon' => 12.5683,
        ];

        $result = $this->transformer->toWeatherDTO($parameterData, $station);

        $this->assertEquals('2024-01-15 00:00:00', $result->timestamp->format('Y-m-d H:i:s'));
    }

    public function test_transform_forecast_converts_kelvin_to_celsius(): void
    {
        $forecastResponse = [
            'domain' => [
                'axes' => [
                    't' => [
                        'values' => [
                            '2025-01-15T12:00:00Z',
                            '2025-01-15T13:00:00Z',
                        ],
                    ],
                ],
            ],
            'ranges' => [
                'temperature-2m' => [
                    'values' => [288.15, 290.15], // 15°C and 17°C in Kelvin
                ],
                'wind-speed-10m' => [
                    'values' => [5.0, 6.0],
                ],
                'wind-dir-10m' => [
                    'values' => [180, 190],
                ],
                'total-precipitation' => [
                    'values' => [0.5, 1.0],
                ],
                'fraction-of-cloud-cover' => [
                    'values' => [50, 75],
                ],
            ],
        ];

        Carbon::setTestNow('2025-01-15T11:00:00Z');

        $result = $this->transformer->transformForecast($forecastResponse);

        $this->assertCount(2, $result);
        $this->assertEquals(15.0, $result[0]['temperature']);
        $this->assertEquals(17.0, $result[1]['temperature']);
        $this->assertEquals('celsius', $result[0]['temperature_unit']);

        Carbon::setTestNow();
    }

    public function test_transform_forecast_includes_wind_chill_when_applicable(): void
    {
        $forecastResponse = [
            'domain' => [
                'axes' => [
                    't' => [
                        'values' => ['2025-01-15T12:00:00Z'],
                    ],
                ],
            ],
            'ranges' => [
                'temperature-2m' => [
                    'values' => [278.15], // 5°C
                ],
                'wind-speed-10m' => [
                    'values' => [8.0], // Strong wind for chill
                ],
                'wind-dir-10m' => [
                    'values' => [180],
                ],
                'total-precipitation' => [
                    'values' => [0],
                ],
                'fraction-of-cloud-cover' => [
                    'values' => [0],
                ],
            ],
        ];

        Carbon::setTestNow('2025-01-15T11:00:00Z');

        $result = $this->transformer->transformForecast($forecastResponse);

        $this->assertArrayHasKey('wind_chill', $result[0]);
        $this->assertLessThan(5.0, $result[0]['wind_chill']);

        Carbon::setTestNow();
    }

    public function test_transform_forecast_filters_past_timestamps(): void
    {
        $forecastResponse = [
            'domain' => [
                'axes' => [
                    't' => [
                        'values' => [
                            '2020-01-15T12:00:00Z', // Past
                            '2025-01-15T12:00:00Z', // Future
                        ],
                    ],
                ],
            ],
            'ranges' => [
                'temperature-2m' => [
                    'values' => [288.15, 290.15],
                ],
                'wind-speed-10m' => [
                    'values' => [5.0, 6.0],
                ],
                'wind-dir-10m' => [
                    'values' => [180, 190],
                ],
                'total-precipitation' => [
                    'values' => [0.5, 1.0],
                ],
                'fraction-of-cloud-cover' => [
                    'values' => [50, 75],
                ],
            ],
        ];

        Carbon::setTestNow('2024-06-01T00:00:00Z');

        $result = $this->transformer->transformForecast($forecastResponse);

        // Only future timestamp should remain
        $this->assertCount(1, $result);
        $this->assertEquals('2025-01-15T12:00:00Z', $result[0]['timestamp']);

        Carbon::setTestNow();
    }

    public function test_transform_historical_aggregates_multiple_parameters(): void
    {
        $parameterResponses = [
            'mean_temp' => [
                [
                    'properties' => [
                        'from' => '2024-01-01T00:00:00Z',
                        'value' => 5.5,
                        'unit' => 'degree celsius',
                    ],
                ],
            ],
            'acc_precip' => [
                [
                    'properties' => [
                        'from' => '2024-01-01T00:00:00Z',
                        'value' => 10.2,
                        'unit' => 'mm',
                    ],
                ],
            ],
        ];

        $result = $this->transformer->transformHistorical($parameterResponses, 'day');

        $this->assertCount(1, $result);
        $this->assertEquals('2024-01-01T00:00:00Z', $result[0]['timestamp']);
        $this->assertEquals(5.5, $result[0]['temperature']['value']);
        $this->assertEquals('degree celsius', $result[0]['temperature']['unit']);
        $this->assertEquals(10.2, $result[0]['precipitation']['value']);
        $this->assertEquals('mm', $result[0]['precipitation']['unit']);
    }

    public function test_transform_historical_includes_wind_chill(): void
    {
        $parameterResponses = [
            'mean_temp' => [
                [
                    'properties' => [
                        'from' => '2024-01-01T00:00:00Z',
                        'value' => 2.0,
                        'unit' => 'celsius',
                    ],
                ],
            ],
            'mean_wind_speed' => [
                [
                    'properties' => [
                        'from' => '2024-01-01T00:00:00Z',
                        'value' => 6.0,
                        'unit' => 'meters_per_second',
                    ],
                ],
            ],
        ];

        $result = $this->transformer->transformHistorical($parameterResponses, 'day');

        $this->assertArrayHasKey('wind_chill', $result[0]);
        $this->assertLessThan(2.0, $result[0]['wind_chill']['value']);
    }

    public function test_transform_historical_maps_parameter_names(): void
    {
        $parameterResponses = [
            'mean_temp' => [
                [
                    'properties' => [
                        'from' => '2024-01-01T00:00:00Z',
                        'value' => 5.5,
                    ],
                ],
            ],
            'bright_sunshine' => [
                [
                    'properties' => [
                        'from' => '2024-01-01T00:00:00Z',
                        'value' => 8.5,
                    ],
                ],
            ],
        ];

        $result = $this->transformer->transformHistorical($parameterResponses, 'day');

        $this->assertArrayHasKey('temperature', $result[0]);
        $this->assertArrayHasKey('sunshine_hours', $result[0]);
    }

    public function test_transform_historical_uses_fallback_units(): void
    {
        $parameterResponses = [
            'mean_temp' => [
                [
                    'properties' => [
                        'from' => '2024-01-01T00:00:00Z',
                        'value' => 5.5,
                        // No unit specified
                    ],
                ],
            ],
        ];

        $result = $this->transformer->transformHistorical($parameterResponses, 'day');

        $this->assertEquals('celsius', $result[0]['temperature']['unit']);
    }
}
