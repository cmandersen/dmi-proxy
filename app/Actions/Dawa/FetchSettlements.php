<?php

namespace App\Actions\Dawa;

use Illuminate\Support\Facades\Http;

class FetchSettlements
{
    public function execute(?string $query = null, array $filters = [], int $timeout = 60): array
    {
        try {
            $params = array_merge(
                $query ? ['q' => $query] : [],
                $filters
            );

            $response = Http::timeout($timeout)
                ->get('https://api.dataforsyningen.dk/steder', $params);

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (\Exception) {
            return [];
        }
    }
}
