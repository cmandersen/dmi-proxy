<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class HistoricalWeatherDTO extends Data
{
    public function __construct(
        public LocationData $location,
        public PeriodData $period,
        public array $data,
    ) {}
}
