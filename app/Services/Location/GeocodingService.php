<?php

namespace App\Services\Location;

use App\Actions\Dawa\FetchMunicipalities;
use App\Actions\Dawa\FetchPostalCodes;
use App\Actions\Dawa\FetchSettlements;
use App\Exceptions\WeatherServiceException;
use Illuminate\Support\Facades\Cache;

class GeocodingService
{
    public function __construct(
        private FetchPostalCodes $fetchPostalCodes,
        private FetchMunicipalities $fetchMunicipalities,
        private FetchSettlements $fetchSettlements,
    ) {}

    public function geocode(string $location): array
    {
        // Check if location is in "lat,lon" format (don't cache this)
        if (str_contains($location, ',')) {
            $coords = $this->parseCoordinates($location);
            if ($coords) {
                return $coords;
            }
        }

        $normalizedLocation = $this->normalize($location);
        $cacheKey = "geocode:{$normalizedLocation}";

        return Cache::remember($cacheKey, config('services.dmi.cache_ttl.geocoding', 86400), function () use ($location) {
            $dawaCoords = $this->geocodeWithDawa($location);

            if ($dawaCoords) {
                return $dawaCoords;
            }

            throw new WeatherServiceException(
                "Unable to geocode location: {$location}. Try using city name, postal code, or lat,lon coordinates. If the cache is empty, run 'php artisan weather:cache-cities' to populate location data.",
                400
            );
        });
    }

    private function geocodeWithDawa(string $location): ?array
    {
        try {
            // Try postal code first (postnumre endpoint)
            if (is_numeric($location) && strlen($location) === 4) {
                $results = $this->fetchPostalCodes->execute($location, timeout: 5);

                if (! empty($results)) {
                    $data = $results[0] ?? null;
                    if ($data && isset($data['visueltcenter'])) {
                        return [
                            'lat' => $data['visueltcenter'][1],
                            'lon' => $data['visueltcenter'][0],
                        ];
                    }
                }
            }

            // Try kommune (municipality) first - more reliable for cities
            $results = $this->fetchMunicipalities->execute($location, timeout: 5);

            if (! empty($results)) {
                // Find exact match first
                foreach ($results as $result) {
                    $name = mb_strtolower($result['navn'] ?? '', 'UTF-8');
                    if ($name === mb_strtolower($location, 'UTF-8')) {
                        if (isset($result['visueltcenter'])) {
                            return [
                                'lat' => $result['visueltcenter'][1],
                                'lon' => $result['visueltcenter'][0],
                            ];
                        }
                    }
                }
                // If no exact match, use first result
                $data = $results[0] ?? null;
                if ($data && isset($data['visueltcenter'])) {
                    return [
                        'lat' => $data['visueltcenter'][1],
                        'lon' => $data['visueltcenter'][0],
                    ];
                }
            }

            // Try city/place name (steder endpoint) with hovedtype=by for actual cities
            $results = $this->fetchSettlements->execute(
                $location,
                filters: [
                    'hovedtype' => 'bebyggelse',
                    'undertype' => 'by',
                ],
                timeout: 5
            );

            if (! empty($results)) {
                $data = $results[0] ?? null;
                if ($data && isset($data['visueltcenter'])) {
                    return [
                        'lat' => $data['visueltcenter'][1],
                        'lon' => $data['visueltcenter'][0],
                    ];
                }
            }

            return null;
        } catch (\Exception) {
            // If DAWA fails, return null to allow cache fallback
            return null;
        }
    }

    private function normalize(string $input): string
    {
        return mb_strtolower(trim($input), 'UTF-8');
    }

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

            if ($lat >= -90 && $lat <= 90 && $lon >= -180 && $lon <= 180) {
                return ['lat' => $lat, 'lon' => $lon];
            }
        }

        return null;
    }
}
