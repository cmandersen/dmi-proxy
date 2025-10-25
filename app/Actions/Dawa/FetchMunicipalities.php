<?php

namespace App\Actions\Dawa;

use Illuminate\Support\Facades\Http;

class FetchMunicipalities
{
    public function execute(?string $query = null, int $timeout = 30): array
    {
        try {
            $params = $query ? ['q' => $query] : [];

            $response = Http::timeout($timeout)
                ->get('https://api.dataforsyningen.dk/kommuner', $params);

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (\Exception) {
            return [];
        }
    }
}
