<?php

namespace App\Actions\Dawa;

use Illuminate\Support\Facades\Http;

class FetchPostalCodes
{
    public function execute(?string $postalCode = null, int $timeout = 30): array
    {
        try {
            $params = $postalCode ? ['nr' => $postalCode] : [];

            $response = Http::timeout($timeout)
                ->get('https://api.dataforsyningen.dk/postnumre', $params);

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (\Exception) {
            return [];
        }
    }
}
