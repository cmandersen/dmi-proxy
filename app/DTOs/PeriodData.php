<?php

namespace App\DTOs;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer;

class PeriodData extends Data
{
    public function __construct(
        #[WithTransformer(DateTimeInterfaceTransformer::class, format: 'c')]
        public Carbon $from,
        #[WithTransformer(DateTimeInterfaceTransformer::class, format: 'c')]
        public Carbon $to,
        public string $resolution,
    ) {}
}
