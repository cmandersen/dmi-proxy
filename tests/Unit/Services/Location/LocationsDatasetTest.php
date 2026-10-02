<?php

namespace Tests\Unit\Services\Location;

use App\Services\Location\GeocodingService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocationsDatasetTest extends TestCase
{
    /**
     * @var array{aliases: array<string, string>, locations: array<string, array{0: float, 1: float}>}
     */
    private array $dataset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dataset = json_decode(
            file_get_contents(database_path('data/locations.json')),
            true,
            flags: JSON_THROW_ON_ERROR
        );
    }

    public function test_dataset_has_expected_coverage(): void
    {
        $postalCodes = array_filter(array_keys($this->dataset['locations']), fn ($key) => preg_match('/^\d{4}$/', (string) $key));

        $this->assertGreaterThan(1000, count($postalCodes));
        $this->assertGreaterThan(8000, count($this->dataset['locations']) - count($postalCodes));
    }

    public function test_all_locations_are_within_denmark(): void
    {
        $outside = array_filter(
            $this->dataset['locations'],
            fn (array $coords) => $coords[0] < 54.0 || $coords[0] > 58.0 || $coords[1] < 7.5 || $coords[1] > 15.5
        );

        $this->assertSame([], $outside);
    }

    public function test_location_keys_are_normalized(): void
    {
        foreach (array_keys($this->dataset['locations']) as $key) {
            $key = (string) $key;
            $this->assertSame(mb_strtolower(trim($key), 'UTF-8'), $key);
        }
    }

    public function test_all_aliases_point_to_existing_locations(): void
    {
        foreach ($this->dataset['aliases'] as $alias => $target) {
            $this->assertArrayHasKey($target, $this->dataset['locations'], "Alias {$alias} points to missing {$target}");
            $this->assertArrayNotHasKey($alias, $this->dataset['locations'], "Alias {$alias} shadows a location");
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function knownLocationsProvider(): array
    {
        return [
            'capital' => ['København'],
            'english alias' => ['Copenhagen'],
            'old spelling' => ['Århus'],
            'bornholm' => ['Rønne'],
            'central copenhagen postal code' => ['1050'],
            'west coast postal code' => ['6950'],
            'svaneke postal code' => ['3740'],
            'small town' => ['Skagen'],
        ];
    }

    #[DataProvider('knownLocationsProvider')]
    public function test_known_locations_geocode_with_bundled_dataset(string $location): void
    {
        $result = app(GeocodingService::class)->geocode($location);

        $this->assertGreaterThanOrEqual(54.0, $result['lat']);
        $this->assertLessThanOrEqual(58.0, $result['lat']);
        $this->assertGreaterThanOrEqual(7.5, $result['lon']);
        $this->assertLessThanOrEqual(15.5, $result['lon']);
    }
}
