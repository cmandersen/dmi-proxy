<?php

namespace Tests\Unit\Services\Weather;

use App\Services\Location\GeocodingService;
use App\Services\Weather\DmiWeatherService;
use App\Services\Weather\WeatherTransformer;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DmiWeatherServiceTest extends TestCase
{
    private DmiWeatherService $service;

    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        parent::setUp();

        $geocoding = $this->createMock(GeocodingService::class);
        $transformer = $this->createMock(WeatherTransformer::class);

        $this->service = new DmiWeatherService($transformer, $geocoding);
        $this->reflection = new ReflectionClass(DmiWeatherService::class);
    }

    public function test_haversine_distance_calculates_correctly_for_copenhagen_aarhus(): void
    {
        $method = $this->reflection->getMethod('haversineDistance');
        $method->setAccessible(true);

        // Copenhagen: 55.6761, 12.5683
        // Aarhus: 56.1629, 10.2039
        $distance = $method->invoke($this->service, 55.6761, 12.5683, 56.1629, 10.2039);

        // Distance should be approximately 140-150 km
        $this->assertGreaterThan(130, $distance);
        $this->assertLessThan(160, $distance);
    }

    public function test_haversine_distance_returns_zero_for_same_point(): void
    {
        $method = $this->reflection->getMethod('haversineDistance');
        $method->setAccessible(true);

        $distance = $method->invoke($this->service, 55.6761, 12.5683, 55.6761, 12.5683);

        $this->assertEquals(0, $distance);
    }

    public function test_haversine_distance_is_symmetric(): void
    {
        $method = $this->reflection->getMethod('haversineDistance');
        $method->setAccessible(true);

        $distance1 = $method->invoke($this->service, 55.6761, 12.5683, 56.1629, 10.2039);
        $distance2 = $method->invoke($this->service, 56.1629, 10.2039, 55.6761, 12.5683);

        $this->assertEquals($distance1, $distance2);
    }

    public function test_haversine_distance_handles_negative_coordinates(): void
    {
        $method = $this->reflection->getMethod('haversineDistance');
        $method->setAccessible(true);

        // Test with coordinates that could be negative
        $distance = $method->invoke($this->service, -33.8688, 151.2093, -37.8136, 144.9631);

        // Sydney to Melbourne is approximately 713 km
        $this->assertGreaterThan(650, $distance);
        $this->assertLessThan(780, $distance);
    }

    public function test_create_bounding_box_generates_correct_bounds(): void
    {
        $method = $this->reflection->getMethod('createBoundingBox');
        $method->setAccessible(true);

        // Copenhagen center with 50km radius
        $bbox = $method->invoke($this->service, 55.6761, 12.5683, 50);

        $this->assertArrayHasKey('minLat', $bbox);
        $this->assertArrayHasKey('maxLat', $bbox);
        $this->assertArrayHasKey('minLon', $bbox);
        $this->assertArrayHasKey('maxLon', $bbox);

        // Min should be less than center
        $this->assertLessThan(55.6761, $bbox['minLat']);
        $this->assertLessThan(12.5683, $bbox['minLon']);

        // Max should be greater than center
        $this->assertGreaterThan(55.6761, $bbox['maxLat']);
        $this->assertGreaterThan(12.5683, $bbox['maxLon']);
    }

    public function test_create_bounding_box_is_roughly_square_at_equator(): void
    {
        $method = $this->reflection->getMethod('createBoundingBox');
        $method->setAccessible(true);

        // At equator, lat and lon degrees are approximately the same distance
        $bbox = $method->invoke($this->service, 0, 0, 50);

        $latSpan = $bbox['maxLat'] - $bbox['minLat'];
        $lonSpan = $bbox['maxLon'] - $bbox['minLon'];

        // Should be roughly similar (within 10%)
        $this->assertEqualsWithDelta($latSpan, $lonSpan, $latSpan * 0.1);
    }

    public function test_create_bounding_box_adjusts_for_latitude(): void
    {
        $method = $this->reflection->getMethod('createBoundingBox');
        $method->setAccessible(true);

        // At higher latitudes, longitude span should be larger
        $bboxEquator = $method->invoke($this->service, 0, 0, 50);
        $bboxDenmark = $method->invoke($this->service, 56, 10, 50);

        $lonSpanEquator = $bboxEquator['maxLon'] - $bboxEquator['minLon'];
        $lonSpanDenmark = $bboxDenmark['maxLon'] - $bboxDenmark['minLon'];

        // Longitude span should be larger at higher latitude
        $this->assertGreaterThan($lonSpanEquator, $lonSpanDenmark);
    }

    public function test_create_bounding_box_with_different_radii(): void
    {
        $method = $this->reflection->getMethod('createBoundingBox');
        $method->setAccessible(true);

        $bbox10 = $method->invoke($this->service, 55.6761, 12.5683, 10);
        $bbox50 = $method->invoke($this->service, 55.6761, 12.5683, 50);

        $latSpan10 = $bbox10['maxLat'] - $bbox10['minLat'];
        $latSpan50 = $bbox50['maxLat'] - $bbox50['minLat'];

        // 50km radius should have approximately 5x the span of 10km
        $this->assertEqualsWithDelta($latSpan50, $latSpan10 * 5, 0.05);
    }

    public function test_select_closest_station_picks_nearest(): void
    {
        $method = $this->reflection->getMethod('selectClosestStation');
        $method->setAccessible(true);

        $stations = [
            [
                'geometry' => ['coordinates' => [12.5683, 55.6761]],  // Copenhagen
                'properties' => ['stationId' => '06180', 'name' => 'Copenhagen'],
            ],
            [
                'geometry' => ['coordinates' => [10.2039, 56.1629]],  // Aarhus
                'properties' => ['stationId' => '06060', 'name' => 'Aarhus'],
            ],
            [
                'geometry' => ['coordinates' => [9.9217, 57.0488]],  // Aalborg
                'properties' => ['stationId' => '06080', 'name' => 'Aalborg'],
            ],
        ];

        // Query point is close to Copenhagen
        $result = $method->invoke($this->service, $stations, 55.7000, 12.6000);

        $this->assertEquals('06180', $result['id']);
        $this->assertEquals('Copenhagen', $result['name']);
    }

    public function test_select_closest_station_includes_distance(): void
    {
        $method = $this->reflection->getMethod('selectClosestStation');
        $method->setAccessible(true);

        $stations = [
            [
                'geometry' => ['coordinates' => [12.5683, 55.6761]],
                'properties' => ['stationId' => '06180', 'name' => 'Copenhagen'],
            ],
        ];

        $result = $method->invoke($this->service, $stations, 55.6761, 12.5683);

        $this->assertArrayHasKey('distance', $result);
        $this->assertEquals(0, $result['distance']);
    }

    public function test_select_closest_station_returns_correct_structure(): void
    {
        $method = $this->reflection->getMethod('selectClosestStation');
        $method->setAccessible(true);

        $stations = [
            [
                'geometry' => ['coordinates' => [12.5683, 55.6761]],
                'properties' => ['stationId' => '06180', 'name' => 'Copenhagen Station'],
            ],
        ];

        $result = $method->invoke($this->service, $stations, 55.6761, 12.5683);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('distance', $result);
        $this->assertArrayHasKey('lat', $result);
        $this->assertArrayHasKey('lon', $result);

        $this->assertEquals('06180', $result['id']);
        $this->assertEquals('Copenhagen Station', $result['name']);
        $this->assertEquals(55.6761, $result['lat']);
        $this->assertEquals(12.5683, $result['lon']);
    }

    public function test_select_closest_station_with_single_station(): void
    {
        $method = $this->reflection->getMethod('selectClosestStation');
        $method->setAccessible(true);

        $stations = [
            [
                'geometry' => ['coordinates' => [12.5683, 55.6761]],
                'properties' => ['stationId' => '06180', 'name' => 'Only Station'],
            ],
        ];

        // Query point far away
        $result = $method->invoke($this->service, $stations, 50.0, 8.0);

        // Should still return the only available station
        $this->assertEquals('06180', $result['id']);
        $this->assertGreaterThan(500, $result['distance']); // Should be far
    }

    public function test_haversine_distance_with_very_small_distances(): void
    {
        $method = $this->reflection->getMethod('haversineDistance');
        $method->setAccessible(true);

        // Two points 100 meters apart (approximately)
        $distance = $method->invoke($this->service, 55.6761, 12.5683, 55.6770, 12.5683);

        // Should be approximately 0.1 km (100 meters)
        $this->assertLessThan(1, $distance);
        $this->assertGreaterThan(0.05, $distance);
    }

    public function test_haversine_distance_with_large_distances(): void
    {
        $method = $this->reflection->getMethod('haversineDistance');
        $method->setAccessible(true);

        // Copenhagen to New York (approximately 6200 km)
        $distance = $method->invoke($this->service, 55.6761, 12.5683, 40.7128, -74.0060);

        $this->assertGreaterThan(6000, $distance);
        $this->assertLessThan(6500, $distance);
    }
}
