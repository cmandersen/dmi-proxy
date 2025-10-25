<?php

namespace App\DTOs;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer;

class ForecastDataDTO extends Data
{
    public function __construct(
        public LocationData $location,
        #[WithTransformer(DateTimeInterfaceTransformer::class, format: 'c')]
        public Carbon $generated_at,
        public array $forecast,
    ) {}
}
