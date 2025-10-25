<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class WindData extends Data
{
    public function __construct(
        public ?float $speed,
        public ?float $direction,
        public string $unit = 'meters_per_second',
        public float|Optional|null $chill = null,
        public string|Optional $chill_unit = 'celsius',
    ) {}
}
