<?php

namespace Tests\Feature\Api;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class ForecastResilienceTest extends TestCase
{
    private const FORECAST_URL = 'opendataapi.dmi.dk/v1/forecastedr/collections/harmonie_dini_sf/position*';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Sleep::fake();
    }

    public function test_forecast_is_fetched_once_for_different_hour_values(): void
    {
        Http::fake([self::FORECAST_URL => Http::response($this->forecastPayload(48))]);

        $this->getJson('/api/weather/forecast/copenhagen?hours=24')
            ->assertOk()
            ->assertJsonCount(24, 'forecast')
            ->assertJsonPath('stale', false);

        $this->getJson('/api/weather/forecast/copenhagen?hours=48')
            ->assertOk()
            ->assertJsonCount(48, 'forecast');

        Http::assertSentCount(1);
    }

    public function test_serves_last_good_forecast_when_dmi_is_overloaded(): void
    {
        Http::fake([self::FORECAST_URL => Http::sequence()
            ->push($this->forecastPayload(48))
            ->push(['status' => 429, 'error' => 'Too Many Requests', 'message' => 'Server is busy. Please try again later.'], 429),
        ]);

        $this->getJson('/api/weather/forecast/copenhagen?hours=24')->assertOk();

        $this->travel(31)->minutes();

        $this->getJson('/api/weather/forecast/copenhagen?hours=24')
            ->assertOk()
            ->assertJsonPath('stale', true)
            ->assertJsonCount(24, 'forecast');
    }

    public function test_stale_forecast_omits_hours_that_have_passed(): void
    {
        Http::fake([self::FORECAST_URL => Http::sequence()
            ->push($this->forecastPayload(48))
            ->push([], 503)
            ->push([], 503)
            ->push([], 503),
        ]);

        $this->getJson('/api/weather/forecast/copenhagen?hours=48')->assertOk();

        $this->travel(3)->hours();

        $response = $this->getJson('/api/weather/forecast/copenhagen?hours=48')
            ->assertOk()
            ->assertJsonPath('stale', true);

        $this->assertCount(45, $response->json('forecast'));
        foreach ($response->json('forecast') as $dataPoint) {
            $this->assertTrue(now()->lt($dataPoint['timestamp']));
        }
    }

    public function test_serves_last_good_forecast_when_dmi_is_unreachable(): void
    {
        $attempts = 0;

        Http::fake(function () use (&$attempts) {
            $attempts++;

            if ($attempts > 1) {
                throw new ConnectionException('Could not resolve host');
            }

            return Http::response($this->forecastPayload(48));
        });

        $this->getJson('/api/weather/forecast/copenhagen')->assertOk();

        $this->travel(31)->minutes();

        $this->getJson('/api/weather/forecast/copenhagen')
            ->assertOk()
            ->assertJsonPath('stale', true);
    }

    public function test_overload_without_cached_forecast_returns_503_with_retry_after(): void
    {
        Http::fake([self::FORECAST_URL => Http::response(['message' => 'Server is busy. Please try again later.'], 429)]);

        $this->getJson('/api/weather/forecast/copenhagen')
            ->assertStatus(503)
            ->assertHeader('Retry-After', '60')
            ->assertJsonPath('error', 'Failed to fetch forecast data');
    }

    public function test_client_errors_from_dmi_return_502(): void
    {
        Http::fake([self::FORECAST_URL => Http::response(['message' => 'Bad request'], 400)]);

        $this->getJson('/api/weather/forecast/copenhagen')
            ->assertStatus(502)
            ->assertHeaderMissing('Retry-After');
    }

    public function test_overloaded_responses_are_not_retried(): void
    {
        Http::fake([self::FORECAST_URL => Http::response([], 429)]);

        $this->getJson('/api/weather/forecast/copenhagen')->assertStatus(503);

        Http::assertSentCount(1);
        Sleep::assertNeverSlept();
    }

    public function test_server_errors_are_retried_with_backoff(): void
    {
        Http::fake([self::FORECAST_URL => Http::sequence()
            ->push([], 502)
            ->push([], 503)
            ->push($this->forecastPayload(48)),
        ]);

        $this->getJson('/api/weather/forecast/copenhagen')
            ->assertOk()
            ->assertJsonPath('stale', false);

        Http::assertSentCount(3);
        Sleep::assertSleptTimes(2);
    }

    public function test_server_errors_give_up_after_three_attempts(): void
    {
        Http::fake([self::FORECAST_URL => Http::response([], 500)]);

        $this->getJson('/api/weather/forecast/copenhagen')->assertStatus(503);

        Http::assertSentCount(3);
    }

    public function test_failed_fetch_does_not_overwrite_last_good_forecast(): void
    {
        Http::fake([self::FORECAST_URL => Http::sequence()
            ->push($this->forecastPayload(48))
            ->push([], 429)
            ->push([], 429),
        ]);

        $this->getJson('/api/weather/forecast/copenhagen')->assertOk();

        $this->travel(31)->minutes();
        $this->getJson('/api/weather/forecast/copenhagen')->assertJsonPath('stale', true);

        $this->travel(31)->minutes();
        $this->getJson('/api/weather/forecast/copenhagen')
            ->assertOk()
            ->assertJsonPath('stale', true);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'harmonie_dini_sf'));
    }

    public function test_last_good_forecast_expires(): void
    {
        Http::fake([self::FORECAST_URL => Http::sequence()
            ->push($this->forecastPayload(48))
            ->push([], 429),
        ]);

        $this->getJson('/api/weather/forecast/copenhagen')->assertOk();

        $this->travel(7)->hours();

        $this->getJson('/api/weather/forecast/copenhagen')->assertStatus(503);
    }

    /**
     * @return array<string, mixed>
     */
    private function forecastPayload(int $hours): array
    {
        $start = now()->addHour()->startOfHour();
        $timestamps = [];

        for ($hour = 0; $hour < $hours; $hour++) {
            $timestamps[] = $start->copy()->addHours($hour)->toIso8601ZuluString();
        }

        return [
            'domain' => ['axes' => ['t' => ['values' => $timestamps]]],
            'ranges' => [
                'temperature-2m' => ['values' => array_fill(0, $hours, 283.15)],
                'wind-speed-10m' => ['values' => array_fill(0, $hours, 4.0)],
                'wind-dir-10m' => ['values' => array_fill(0, $hours, 180)],
                'total-precipitation' => ['values' => array_fill(0, $hours, 0.0)],
                'fraction-of-cloud-cover' => ['values' => array_fill(0, $hours, 0.5)],
            ],
        ];
    }
}
