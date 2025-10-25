<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class LocationData extends Data
{
    public function __construct(
        public string $name,
        public CoordinatesData $coordinates,
        public ?string $station = null,
    ) {}
}
