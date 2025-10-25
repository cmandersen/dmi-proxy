<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class CoordinatesData extends Data
{
    public function __construct(
        public float $lat,
        public float $lon,
    ) {}
}
