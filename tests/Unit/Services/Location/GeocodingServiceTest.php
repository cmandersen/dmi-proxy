<?php

namespace Tests\Unit\Services\Location;

use App\Exceptions\WeatherServiceException;
use App\Services\Location\GeocodingService;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class GeocodingServiceTest extends TestCase
{
    private GeocodingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.locations.path' => base_path('tests/Fixtures/locations.json')]);

        $this->service = app(GeocodingService::class);
    }

    public function test_geocodes_coordinates_directly(): void
    {
        $result = $this->service->geocode('55.6761,12.5683');

        $this->assertSame(['lat' => 55.68, 'lon' => 12.57], $result);
    }

    public function test_geocodes_coordinates_with_spaces(): void
    {
        $result = $this->service->geocode('55.6761, 12.5683');

        $this->assertSame(['lat' => 55.68, 'lon' => 12.57], $result);
    }

    public function test_geocodes_city_name_from_dataset(): void
    {
        $result = $this->service->geocode('vejle');

        $this->assertSame(['lat' => 55.71, 'lon' => 9.54], $result);
    }

    public function test_geocodes_danish_characters(): void
    {
        $result = $this->service->geocode('København');

        $this->assertSame(['lat' => 55.7, 'lon' => 12.49], $result);
    }

    public function test_geocodes_postal_code(): void
    {
        $result = $this->service->geocode('8000');

        $this->assertSame(['lat' => 56.15, 'lon' => 10.28], $result);
    }

    public function test_resolves_aliases(): void
    {
        $this->assertSame($this->service->geocode('københavn'), $this->service->geocode('Copenhagen'));
        $this->assertSame($this->service->geocode('aarhus'), $this->service->geocode('Århus'));
    }

    public function test_geocodes_city_case_and_whitespace_insensitive(): void
    {
        $expected = $this->service->geocode('odense');

        $this->assertSame($expected, $this->service->geocode('ODENSE'));
        $this->assertSame($expected, $this->service->geocode('  OdEnSe  '));
    }

    public function test_does_not_make_http_requests(): void
    {
        Http::fake();

        $this->service->geocode('aalborg');

        Http::assertNothingSent();
    }

    public function test_throws_exception_for_unknown_location(): void
    {
        try {
            $this->service->geocode('unknowncityxyz');
            $this->fail('Expected WeatherServiceException was not thrown.');
        } catch (WeatherServiceException $exception) {
            $this->assertSame(400, $exception->getCode());
            $this->assertStringContainsString('Unable to geocode location: unknowncityxyz', $exception->getMessage());
            $this->assertStringNotContainsString('Did you mean', $exception->getMessage());
        }
    }

    public function test_suggests_close_matches_for_misspelled_location(): void
    {
        try {
            $this->service->geocode('Aalborgg');
            $this->fail('Expected WeatherServiceException was not thrown.');
        } catch (WeatherServiceException $exception) {
            $this->assertSame(400, $exception->getCode());
            $this->assertStringContainsString('Did you mean: aalborg?', $exception->getMessage());
        }
    }

    public function test_does_not_suggest_for_very_short_input(): void
    {
        try {
            $this->service->geocode('aa');
            $this->fail('Expected WeatherServiceException was not thrown.');
        } catch (WeatherServiceException $exception) {
            $this->assertStringNotContainsString('Did you mean', $exception->getMessage());
        }
    }

    public function test_unknown_postal_code_is_rejected(): void
    {
        $this->expectException(WeatherServiceException::class);
        $this->expectExceptionCode(400);

        $this->service->geocode('9999');
    }

    public function test_rejects_invalid_coordinate_format(): void
    {
        $this->expectException(WeatherServiceException::class);

        $this->service->geocode('55.6761');
    }

    public function test_non_numeric_comma_separated_input_is_treated_as_a_name(): void
    {
        $this->expectException(WeatherServiceException::class);
        $this->expectExceptionMessage('Unable to geocode location: Odense, Fyn');

        $this->service->geocode('Odense, Fyn');
    }

    public function test_throws_when_dataset_is_missing(): void
    {
        config(['services.locations.path' => base_path('tests/Fixtures/does-not-exist.json')]);

        $this->expectException(RuntimeException::class);

        $this->service->geocode('aalborg');
    }

    public function test_rounds_coordinates_to_two_decimals(): void
    {
        $result = $this->service->geocode('56.117904874739,10.182675838818');

        $this->assertSame(56.12, $result['lat']);
        $this->assertSame(10.18, $result['lon']);
    }

    public function test_trims_whitespace_around_coordinates(): void
    {
        $result = $this->service->geocode('  56.117904874739 ,   10.182675838818  ');

        $this->assertSame(56.12, $result['lat']);
        $this->assertSame(10.18, $result['lon']);
    }

    public function test_nearby_coordinates_resolve_to_same_rounded_value(): void
    {
        $this->assertSame(
            $this->service->geocode('56.1179,10.1826'),
            $this->service->geocode('56.1201,10.1849')
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function outOfBoundsCoordinatesProvider(): array
    {
        return [
            'new york' => ['40.7,-74.0'],
            'null island' => ['0,0'],
            'latitude too low' => ['53.99,10.0'],
            'latitude too high' => ['58.01,10.0'],
            'longitude too low' => ['56.0,7.49'],
            'longitude too high' => ['56.0,15.51'],
            'beyond valid ranges' => ['200,300'],
        ];
    }

    #[DataProvider('outOfBoundsCoordinatesProvider')]
    public function test_rejects_coordinates_outside_denmark(string $location): void
    {
        try {
            $this->service->geocode($location);
            $this->fail('Expected WeatherServiceException was not thrown.');
        } catch (WeatherServiceException $exception) {
            $this->assertSame(400, $exception->getCode());
            $this->assertSame('Coordinates must be within Denmark.', $exception->getMessage());
        }
    }

    /**
     * @return array<string, array{string, float, float}>
     */
    public static function edgeCoordinatesProvider(): array
    {
        return [
            'south west corner' => ['54.0,7.5', 54.0, 7.5],
            'north east corner' => ['58.0,15.5', 58.0, 15.5],
            'bornholm' => ['55.1,14.9', 55.1, 14.9],
        ];
    }

    #[DataProvider('edgeCoordinatesProvider')]
    public function test_accepts_coordinates_at_edges_and_bornholm(string $location, float $lat, float $lon): void
    {
        $result = $this->service->geocode($location);

        $this->assertSame($lat, $result['lat']);
        $this->assertSame($lon, $result['lon']);
    }
}
