<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class TemperatureData extends Data
{
    public function __construct(
        public ?float $value,
        public string $unit = 'celsius',
    ) {}
}
