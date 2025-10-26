<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Simulate cached data from CacheDanishCities command
        Cache::put('geocode:copenhagen', ['lat' => 55.6761, 'lon' => 12.5683], 86400);
    }

    public function test_can_get_current_weather_for_copenhagen(): void
    {
        Http::fake([
            'dmigw.govcloud.dk/v2/metObs/collections/station/items*' => Http::response([
                'features' => [
                    [
                        'geometry' => ['coordinates' => [12.5683, 55.6761]],
                        'properties' => [
                            'stationId' => '06180',
                            'name' => 'Copenhagen',
                        ],
                    ],
                ],
            ]),
            'dmigw.govcloud.dk/v2/metObs/collections/observation/items*' => Http::response([
                'features' => [
                    [
                        'properties' => [
                            'observed' => '2024-01-15T12:00:00Z',
                            'value' => 15.2,
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->getJson('/api/weather/current/copenhagen');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'location' => ['name', 'coordinates', 'station'],
                'timestamp',
                'temperature' => ['value', 'unit'],
            ]);
    }

    public function test_can_get_current_weather_with_coordinates(): void
    {
        Http::fake([
            'dmigw.govcloud.dk/v2/metObs/collections/station/items*' => Http::response([
                'features' => [
                    [
                        'geometry' => ['coordinates' => [12.5683, 55.6761]],
                        'properties' => [
                            'stationId' => '06180',
                            'name' => 'Copenhagen',
                        ],
                    ],
                ],
            ]),
            'dmigw.govcloud.dk/v2/metObs/collections/observation/items*' => Http::response([
                'features' => [
                    [
                        'properties' => [
                            'observed' => '2024-01-15T12:00:00Z',
                            'value' => 15.2,
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->getJson('/api/weather/current/55.6761,12.5683');

        $response->assertStatus(200);
    }

    public function test_returns_error_for_unknown_location(): void
    {
        $response = $this->getJson('/api/weather/current/unknowncity');

        $response->assertStatus(400)
            ->assertJsonFragment([
                'location' => 'unknowncity',
            ])
            ->assertJsonPath('error', fn ($error) => str_contains($error, 'Unable to geocode location: unknowncity'));
    }

    public function test_weather_data_is_cached(): void
    {
        Http::fake([
            'api.dataforsyningen.dk/postnumre*' => Http::response([
                [
                    'nr' => '1000',
                    'navn' => 'København K',
                    'visueltcenter' => [55.6761, 12.5683],
                ],
            ]),
            'dmigw.govcloud.dk/v2/metObs/collections/station/items*' => Http::response([
                'features' => [
                    [
                        'geometry' => ['coordinates' => [12.5683, 55.6761]],
                        'properties' => [
                            'stationId' => '06180',
                            'name' => 'Copenhagen',
                        ],
                    ],
                ],
            ]),
            'dmigw.govcloud.dk/v2/metObs/collections/observation/items*' => Http::response([
                'features' => [
                    [
                        'properties' => [
                            'observed' => '2024-01-15T12:00:00Z',
                            'value' => 15.2,
                        ],
                    ],
                ],
            ]),
        ]);

        // First call - should hit API
        $response1 = $this->getJson('/api/weather/current/copenhagen');
        $response1->assertStatus(200);

        // Second call - should use cache (HTTP fake won't be hit again if cached)
        $response2 = $this->getJson('/api/weather/current/copenhagen');
        $response2->assertStatus(200);

        // Both responses should be identical
        $this->assertEquals($response1->json(), $response2->json());
    }

    public function test_can_get_forecast_for_location(): void
    {
        Http::fake([
            'dmigw.govcloud.dk/v1/forecastedr/collections/harmonie_dini_sf/position*' => Http::response([
                'domain' => [
                    'axes' => [
                        't' => [
                            'values' => [
                                '2025-12-01T12:00:00Z',
                                '2025-12-01T13:00:00Z',
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
            ]),
        ]);

        $response = $this->getJson('/api/weather/forecast/copenhagen');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'location',
                'generated_at',
                'forecast' => [
                    '*' => [
                        'timestamp',
                        'temperature',
                        'wind_speed',
                    ],
                ],
            ]);
    }

    public function test_forecast_respects_hours_parameter(): void
    {
        Http::fake([
            'dmigw.govcloud.dk/v1/forecastedr/collections/harmonie_dini_sf/position*' => Http::response([
                'domain' => [
                    'axes' => [
                        't' => [
                            'values' => array_map(
                                fn ($i) => now()->addHours($i)->toIso8601String(),
                                range(1, 72)
                            ),
                        ],
                    ],
                ],
                'ranges' => [
                    'temperature-2m' => ['values' => array_fill(0, 72, 288.15)],
                    'wind-speed-10m' => ['values' => array_fill(0, 72, 5.0)],
                    'wind-dir-10m' => ['values' => array_fill(0, 72, 180)],
                    'total-precipitation' => ['values' => array_fill(0, 72, 0.5)],
                    'fraction-of-cloud-cover' => ['values' => array_fill(0, 72, 50)],
                ],
            ]),
        ]);

        $response = $this->getJson('/api/weather/forecast/copenhagen?hours=10');

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(10, count($response->json('forecast')));
    }

    public function test_can_get_historical_weather(): void
    {
        Http::fake([
            'dmigw.govcloud.dk/v2/metObs/collections/station/items*' => Http::response([
                'features' => [
                    [
                        'geometry' => ['coordinates' => [12.5683, 55.6761]],
                        'properties' => [
                            'stationId' => '06180',
                            'name' => 'Copenhagen',
                        ],
                    ],
                ],
            ]),
            'dmigw.govcloud.dk/v2/climateData/collections/stationValue/items*' => Http::response([
                'features' => [
                    [
                        'properties' => [
                            'from' => '2024-01-01T00:00:00Z',
                            'value' => 5.5,
                            'unit' => 'celsius',
                        ],
                    ],
                    [
                        'properties' => [
                            'from' => '2024-01-02T00:00:00Z',
                            'value' => 6.2,
                            'unit' => 'celsius',
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->getJson('/api/weather/historical/copenhagen?from=2024-01-01&to=2024-01-02');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'location',
                'period' => ['from', 'to', 'resolution'],
                'data',
            ]);
    }

    public function test_historical_requires_from_and_to_dates(): void
    {
        $response = $this->getJson('/api/weather/historical/copenhagen');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from', 'to']);
    }

    public function test_historical_validates_date_order(): void
    {
        $response = $this->getJson('/api/weather/historical/copenhagen?from=2024-01-10&to=2024-01-01');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to']);
    }

    public function test_historical_supports_different_resolutions(): void
    {
        Http::fake([
            'dmigw.govcloud.dk/v2/metObs/collections/station/items*' => Http::response([
                'features' => [
                    [
                        'geometry' => ['coordinates' => [12.5683, 55.6761]],
                        'properties' => [
                            'stationId' => '06180',
                            'name' => 'Copenhagen',
                        ],
                    ],
                ],
            ]),
            'dmigw.govcloud.dk/v2/climateData/collections/stationValue/items*' => Http::response([
                'features' => [
                    [
                        'properties' => [
                            'from' => '2024-01-01T00:00:00Z',
                            'value' => 5.5,
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->getJson('/api/weather/historical/copenhagen?from=2024-01-01&to=2024-01-31&resolution=month');

        $response->assertStatus(200)
            ->assertJsonPath('period.resolution', 'month');
    }
}
