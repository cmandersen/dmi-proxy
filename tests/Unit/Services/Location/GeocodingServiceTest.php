<?php

namespace Tests\Unit\Services\Location;

use App\Exceptions\WeatherServiceException;
use App\Services\Location\GeocodingService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeocodingServiceTest extends TestCase
{
    private GeocodingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GeocodingService::class);
    }

    public function test_geocodes_coordinates_directly(): void
    {
        $result = $this->service->geocode('55.6761,12.5683');

        $this->assertEquals(55.6761, $result['lat']);
        $this->assertEquals(12.5683, $result['lon']);
    }

    public function test_geocodes_coordinates_with_spaces(): void
    {
        $result = $this->service->geocode('55.6761, 12.5683');

        $this->assertEquals(55.6761, $result['lat']);
        $this->assertEquals(12.5683, $result['lon']);
    }

    public function test_geocodes_known_city_from_cache(): void
    {
        // Simulate cached data from CacheDanishCities command
        cache()->put('geocode:copenhagen', ['lat' => 55.6761, 'lon' => 12.5683], 86400);

        Http::fake([
            'api.dataforsyningen.dk/*' => Http::response([], 404),
        ]);

        $result = $this->service->geocode('copenhagen');

        $this->assertEquals(55.6761, $result['lat']);
        $this->assertEquals(12.5683, $result['lon']);
    }

    public function test_geocodes_danish_city_name(): void
    {
        // Simulate cached data from CacheDanishCities command
        cache()->put('geocode:københavn', ['lat' => 55.6761, 'lon' => 12.5683], 86400);

        Http::fake([
            'api.dataforsyningen.dk/*' => Http::response([], 404),
        ]);

        $result = $this->service->geocode('København');

        $this->assertEquals(55.6761, $result['lat']);
        $this->assertEquals(12.5683, $result['lon']);
    }

    public function test_geocodes_with_dawa_postal_code(): void
    {
        Http::fake([
            'api.dataforsyningen.dk/postnumre*' => Http::response([
                [
                    'nr' => '8000',
                    'navn' => 'Aarhus C',
                    'visueltcenter' => [10.2039, 56.1629], // [lon, lat] format
                ],
            ]),
        ]);

        $result = $this->service->geocode('8000');

        $this->assertEquals(56.1629, $result['lat']);
        $this->assertEquals(10.2039, $result['lon']);
    }

    public function test_geocodes_with_dawa_city_name(): void
    {
        Http::fake([
            'api.dataforsyningen.dk/postnumre*' => Http::response([], 404),
            'api.dataforsyningen.dk/kommuner*' => Http::response([
                [
                    'kode' => '0630',
                    'navn' => 'Vejle',
                    'visueltcenter' => [9.5357, 55.7074], // [lon, lat] format
                ],
            ]),
        ]);

        $result = $this->service->geocode('Vejle');

        $this->assertEquals(55.7074, $result['lat']);
        $this->assertEquals(9.5357, $result['lon']);
    }

    public function test_normalizes_input_text(): void
    {
        // Simulate cached data from CacheDanishCities command
        cache()->put('geocode:copenhagen', ['lat' => 55.6761, 'lon' => 12.5683], 86400);

        Http::fake([
            'api.dataforsyningen.dk/*' => Http::response([], 404),
        ]);

        $result1 = $this->service->geocode('  COPENHAGEN  ');
        $result2 = $this->service->geocode('copenhagen');

        $this->assertEquals($result1, $result2);
    }

    public function test_throws_exception_for_unknown_location(): void
    {
        Http::fake([
            'api.dataforsyningen.dk/*' => Http::response([], 404),
        ]);

        $this->expectException(WeatherServiceException::class);
        $this->expectExceptionMessage('Unable to geocode location: unknowncityxyz');

        $this->service->geocode('unknowncityxyz');
    }

    public function test_handles_dawa_api_failure_gracefully(): void
    {
        // Simulate cached data from CacheDanishCities command
        cache()->put('geocode:copenhagen', ['lat' => 55.6761, 'lon' => 12.5683], 86400);

        Http::fake([
            'api.dataforsyningen.dk/*' => Http::response([], 500),
        ]);

        // Should fall back to cached data
        $result = $this->service->geocode('copenhagen');

        $this->assertEquals(55.6761, $result['lat']);
        $this->assertEquals(12.5683, $result['lon']);
    }

    public function test_geocodes_postal_code_from_cache(): void
    {
        // Simulate cached data from CacheDanishCities command
        cache()->put('geocode:1000', ['lat' => 55.6761, 'lon' => 12.5683], 86400);

        Http::fake([
            'api.dataforsyningen.dk/*' => Http::response([], 404),
        ]);

        $result = $this->service->geocode('1000');

        $this->assertEquals(55.6761, $result['lat']);
        $this->assertEquals(12.5683, $result['lon']);
    }

    public function test_geocodes_multiple_major_cities(): void
    {
        // Simulate cached data from CacheDanishCities command
        $cities = [
            'copenhagen' => ['lat' => 55.6761, 'lon' => 12.5683],
            'aarhus' => ['lat' => 56.1629, 'lon' => 10.2039],
            'odense' => ['lat' => 55.4038, 'lon' => 10.4024],
            'aalborg' => ['lat' => 57.0488, 'lon' => 9.9217],
        ];

        foreach ($cities as $city => $coords) {
            cache()->put("geocode:{$city}", $coords, 86400);
        }

        Http::fake([
            'api.dataforsyningen.dk/*' => Http::response([], 404),
        ]);

        foreach ($cities as $city => $expected) {
            $result = $this->service->geocode($city);
            $this->assertEquals($expected['lat'], $result['lat'], "Failed for $city");
            $this->assertEquals($expected['lon'], $result['lon'], "Failed for $city");
        }
    }

    public function test_rejects_invalid_coordinate_format(): void
    {
        $this->expectException(WeatherServiceException::class);

        $this->service->geocode('55.6761');  // Missing longitude
    }

    public function test_rejects_invalid_coordinates(): void
    {
        Http::fake([
            'api.dataforsyningen.dk/*' => Http::response([], 404),
        ]);

        $this->expectException(WeatherServiceException::class);

        // Coordinates outside valid ranges (lat must be -90 to 90, lon must be -180 to 180)
        $this->service->geocode('200,300');
    }

    public function test_caches_geocoding_results(): void
    {
        Http::fake([
            'api.dataforsyningen.dk/postnumre*' => Http::response([
                [
                    'nr' => '8000',
                    'navn' => 'Aarhus C',
                    'visueltcenter' => [10.2039, 56.1629], // [lon, lat] format
                ],
            ]),
        ]);

        // First call
        $result1 = $this->service->geocode('8000');

        // Second call - should use cache, won't hit HTTP
        Http::fake([
            'api.dataforsyningen.dk/*' => Http::response([], 500), // Would fail if called
        ]);

        $result2 = $this->service->geocode('8000');

        $this->assertEquals($result1, $result2);
    }

    public function test_handles_dawa_response_with_multiple_results(): void
    {
        Http::fake([
            'api.dataforsyningen.dk/postnumre*' => Http::response([], 404),
            'api.dataforsyningen.dk/kommuner*' => Http::response([
                [
                    'kode' => '0751',
                    'navn' => 'Aarhus',
                    'visueltcenter' => [10.2039, 56.1629], // [lon, lat] format
                ],
                [
                    'kode' => '0760',
                    'navn' => 'Aarhus N',
                    'visueltcenter' => [10.2100, 56.1700], // [lon, lat] format
                ],
            ]),
        ]);

        // Should return first match
        $result = $this->service->geocode('aarhus');

        $this->assertEquals(56.1629, $result['lat']);
        $this->assertEquals(10.2039, $result['lon']);
    }

    public function test_handles_empty_dawa_response(): void
    {
        // Simulate cached data from CacheDanishCities command
        cache()->put('geocode:copenhagen', ['lat' => 55.6761, 'lon' => 12.5683], 86400);

        Http::fake([
            'api.dataforsyningen.dk/postnumre*' => Http::response([]),
        ]);

        // Should fall back to cached data
        $result = $this->service->geocode('copenhagen');

        $this->assertEquals(55.6761, $result['lat']);
        $this->assertEquals(12.5683, $result['lon']);
    }

    public function test_geocodes_city_case_insensitive(): void
    {
        // Simulate cached data from CacheDanishCities command
        cache()->put('geocode:copenhagen', ['lat' => 55.6761, 'lon' => 12.5683], 86400);

        Http::fake([
            'api.dataforsyningen.dk/*' => Http::response([], 404),
        ]);

        $result1 = $this->service->geocode('COPENHAGEN');
        $result2 = $this->service->geocode('copenhagen');
        $result3 = $this->service->geocode('CoPenHaGen');

        $this->assertEquals($result1, $result2);
        $this->assertEquals($result2, $result3);
    }
}
