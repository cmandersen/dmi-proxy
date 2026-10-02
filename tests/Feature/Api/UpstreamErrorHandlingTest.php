<?php

namespace Tests\Feature\Api;

use App\Exceptions\WeatherServiceException;
use App\Services\Weather\DmiWeatherService;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UpstreamErrorHandlingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_connection_exception_returns_503_json_with_retry_after(): void
    {
        Http::fake(fn () => throw new ConnectionException('Could not resolve host'));

        $response = $this->getJson('/api/weather/current/55.68,12.57');

        $response->assertStatus(503)
            ->assertHeader('Retry-After', '60')
            ->assertExactJson([
                'error' => 'Weather service temporarily unavailable',
                'location' => '55.68,12.57',
            ]);
    }

    public function test_connection_exception_is_handled_for_forecast_endpoint(): void
    {
        Http::fake(fn () => throw new ConnectionException('Could not resolve host'));

        $this->getJson('/api/weather/forecast/55.68,12.57')
            ->assertStatus(503)
            ->assertHeader('Retry-After', '60')
            ->assertJsonPath('error', 'Weather service temporarily unavailable');
    }

    public function test_request_exception_returns_503_json(): void
    {
        Http::fake(function (Request $request): never {
            throw new RequestException(new Response(new PsrResponse(500)));
        });

        $this->getJson('/api/weather/current/55.68,12.57')
            ->assertStatus(503)
            ->assertHeader('Retry-After', '60')
            ->assertJsonPath('error', 'Weather service temporarily unavailable')
            ->assertJsonPath('location', '55.68,12.57');
    }

    public function test_non_api_routes_are_not_rendered_as_service_unavailable(): void
    {
        Route::get('/upstream-test', function (): never {
            throw new ConnectionException('Could not resolve host');
        });

        $response = $this->get('/upstream-test');

        $response->assertStatus(500);
        $response->assertHeaderMissing('Retry-After');
    }

    public function test_weather_service_exception_without_code_returns_500(): void
    {
        $this->mock(DmiWeatherService::class)
            ->shouldReceive('getCurrentWeather')
            ->andThrow(new WeatherServiceException('Something broke'));

        $this->getJson('/api/weather/current/55.68,12.57')
            ->assertStatus(500)
            ->assertJsonPath('error', 'Something broke');
    }

    public function test_weather_service_exception_with_invalid_http_code_returns_500(): void
    {
        $this->mock(DmiWeatherService::class)
            ->shouldReceive('getCurrentWeather')
            ->andThrow(new WeatherServiceException('Bad code', 6));

        $this->getJson('/api/weather/current/55.68,12.57')
            ->assertStatus(500);
    }

    public function test_weather_service_exception_with_valid_http_code_keeps_status(): void
    {
        $this->mock(DmiWeatherService::class)
            ->shouldReceive('getCurrentWeather')
            ->andThrow(new WeatherServiceException('No stations', 404));

        $this->getJson('/api/weather/current/55.68,12.57')
            ->assertStatus(404)
            ->assertJsonPath('error', 'No stations');
    }
}
