<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class PressureData extends Data
{
    public function __construct(
        public ?float $value,
        public string $unit = 'hectopascals',
    ) {}
}
