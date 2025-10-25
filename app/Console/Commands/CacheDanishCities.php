<?php

namespace App\Console\Commands;

use App\Actions\Dawa\FetchMunicipalities;
use App\Actions\Dawa\FetchPostalCodes;
use App\Actions\Dawa\FetchSettlements;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CacheDanishCities extends Command
{
    protected $signature = 'weather:cache-cities';

    protected $description = 'Cache all Danish cities from DAWA for geocoding';

    public function __construct(
        private FetchPostalCodes $fetchPostalCodes,
        private FetchMunicipalities $fetchMunicipalities,
        private FetchSettlements $fetchSettlements,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Fetching all Danish cities from DAWA...');

        $cachedCount = 0;
        $ttl = 604800; // 7 days

        // Cache all municipalities (kommuner)
        $this->info('Caching municipalities...');
        $kommuner = $this->fetchKommuner();
        foreach ($kommuner as $kommune) {
            $name = $kommune['navn'] ?? null;
            $center = $kommune['visueltcenter'] ?? null;

            if ($name && $center) {
                $normalizedName = mb_strtolower(trim($name), 'UTF-8');
                $cacheKey = "geocode:{$normalizedName}";

                Cache::put($cacheKey, [
                    'lat' => $center[1],
                    'lon' => $center[0],
                ], $ttl);

                $cachedCount++;
            }
        }

        $this->info("Cached {$cachedCount} municipalities");

        // Cache all settlements (bebyggelser)
        $this->info('Fetching and caching settlements (this may take a few minutes)...');
        $settlements = $this->fetchSettlements();

        $bar = $this->output->createProgressBar(count($settlements));
        $bar->start();

        $settlementCount = 0;
        foreach ($settlements as $settlement) {
            $name = $settlement['primærtnavn'] ?? null;
            $center = $settlement['visueltcenter'] ?? null;

            if ($name && $center) {
                $normalizedName = mb_strtolower(trim($name), 'UTF-8');
                $cacheKey = "geocode:{$normalizedName}";

                // Only cache if not already cached (municipalities take priority)
                if (! Cache::has($cacheKey)) {
                    Cache::put($cacheKey, [
                        'lat' => $center[1],
                        'lon' => $center[0],
                    ], $ttl);

                    $settlementCount++;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Cached {$settlementCount} settlements");

        // Cache postal codes
        $this->info('Caching postal codes...');
        $postalCodes = $this->fetchPostalCodes();

        $postalCount = 0;
        foreach ($postalCodes as $postal) {
            $nr = $postal['nr'] ?? null;
            $center = $postal['visueltcenter'] ?? null;

            if ($nr && $center) {
                $cacheKey = "geocode:{$nr}";

                Cache::put($cacheKey, [
                    'lat' => $center[1],
                    'lon' => $center[0],
                ], $ttl);

                $postalCount++;
            }
        }

        $this->info("Cached {$postalCount} postal codes");

        $totalCached = $cachedCount + $settlementCount + $postalCount;
        $this->info("✓ Successfully cached {$totalCached} locations");
        $this->info('Cache TTL: 7 days');

        return self::SUCCESS;
    }

    private function fetchKommuner(): array
    {
        $results = $this->fetchMunicipalities->execute();

        if (empty($results)) {
            $this->error('Failed to fetch municipalities');
        }

        return $results;
    }

    private function fetchSettlements(): array
    {
        $results = $this->fetchSettlements->execute(
            filters: [
                'hovedtype' => 'Bebyggelse',
                'per_side' => 10000,
            ],
            timeout: 60
        );

        if (empty($results)) {
            $this->error('Failed to fetch settlements');
        }

        return $results;
    }

    private function fetchPostalCodes(): array
    {
        $results = $this->fetchPostalCodes->execute();

        if (empty($results)) {
            $this->error('Failed to fetch postal codes');
        }

        return $results;
    }
}
