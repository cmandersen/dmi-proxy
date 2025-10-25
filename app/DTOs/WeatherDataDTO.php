<?php

namespace App\DTOs;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer;

class WeatherDataDTO extends Data
{
    public function __construct(
        public LocationData $location,
        #[WithTransformer(DateTimeInterfaceTransformer::class, format: 'c')]
        public Carbon $timestamp,
        public TemperatureData $temperature,
        public ?float $humidity,
        public WindData $wind,
        public PressureData $pressure,
    ) {}
}
