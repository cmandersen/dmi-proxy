<?php

namespace App\Services\Location;

use App\Exceptions\WeatherServiceException;
use RuntimeException;

class GeocodingService
{
    private const MIN_LATITUDE = 54.0;

    private const MAX_LATITUDE = 58.0;

    private const MIN_LONGITUDE = 7.5;

    private const MAX_LONGITUDE = 15.5;

    private const COORDINATE_PRECISION = 2;

    private const MAX_SUGGESTION_DISTANCE = 2;

    private const MAX_SUGGESTIONS = 3;

    /**
     * Parsed location datasets keyed by file path, kept for the lifetime of the worker.
     *
     * @var array<string, array{aliases: array<string, string>, locations: array<string, array{0: float, 1: float}>}>
     */
    private static array $datasets = [];

    /**
     * @return array{lat: float, lon: float}
     */
    public function geocode(string $location): array
    {
        if (str_contains($location, ',')) {
            $coords = $this->parseCoordinates($location);
            if ($coords) {
                return $coords;
            }
        }

        $normalizedLocation = $this->normalize($location);
        $dataset = $this->dataset();
        $key = $dataset['aliases'][$normalizedLocation] ?? $normalizedLocation;

        if (isset($dataset['locations'][$key])) {
            [$lat, $lon] = $dataset['locations'][$key];

            return $this->roundCoordinates($lat, $lon);
        }

        $message = "Unable to geocode location: {$location}. Try using a Danish city name, postal code, or lat,lon coordinates.";
        $suggestions = $this->suggest($normalizedLocation, $dataset);

        if ($suggestions !== []) {
            $message .= ' Did you mean: '.implode(', ', $suggestions).'?';
        }

        throw new WeatherServiceException($message, 400);
    }

    /**
     * @param  array{aliases: array<string, string>, locations: array<string, array{0: float, 1: float}>}  $dataset
     * @return list<string>
     */
    private function suggest(string $normalizedLocation, array $dataset): array
    {
        if (mb_strlen($normalizedLocation, 'UTF-8') < 3) {
            return [];
        }

        $distances = [];

        foreach (array_keys($dataset['locations']) as $name) {
            $name = (string) $name;
            $distance = levenshtein($normalizedLocation, $name);

            if ($distance <= self::MAX_SUGGESTION_DISTANCE) {
                $distances[$name] = $distance;
            }
        }

        asort($distances);

        return array_map('strval', array_slice(array_keys($distances), 0, self::MAX_SUGGESTIONS));
    }

    /**
     * @return array{aliases: array<string, string>, locations: array<string, array{0: float, 1: float}>}
     */
    private function dataset(): array
    {
        $path = config('services.locations.path');

        if (! isset(self::$datasets[$path])) {
            $contents = is_readable($path) ? file_get_contents($path) : false;

            if ($contents === false) {
                throw new RuntimeException("Location dataset not found at {$path}.");
            }

            $data = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

            self::$datasets[$path] = [
                'aliases' => $data['aliases'] ?? [],
                'locations' => $data['locations'] ?? [],
            ];
        }

        return self::$datasets[$path];
    }

    private function normalize(string $input): string
    {
        return mb_strtolower(trim($input), 'UTF-8');
    }

    /**
     * @return array{lat: float, lon: float}|null
     */
    private function parseCoordinates(string $location): ?array
    {
        $parts = explode(',', $location);
        if (count($parts) === 2) {
            $latStr = trim($parts[0]);
            $lonStr = trim($parts[1]);

            if (! is_numeric($latStr) || ! is_numeric($lonStr)) {
                return null;
            }

            $lat = (float) $latStr;
            $lon = (float) $lonStr;

            if (
                $lat < self::MIN_LATITUDE || $lat > self::MAX_LATITUDE
                || $lon < self::MIN_LONGITUDE || $lon > self::MAX_LONGITUDE
            ) {
                throw new WeatherServiceException('Coordinates must be within Denmark.', 400);
            }

            return $this->roundCoordinates($lat, $lon);
        }

        return null;
    }

    /**
     * @return array{lat: float, lon: float}
     */
    private function roundCoordinates(float|int|string $lat, float|int|string $lon): array
    {
        return [
            'lat' => round((float) $lat, self::COORDINATE_PRECISION),
            'lon' => round((float) $lon, self::COORDINATE_PRECISION),
        ];
    }
}
