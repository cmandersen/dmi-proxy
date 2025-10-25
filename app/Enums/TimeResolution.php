<?php

namespace App\Enums;

enum TimeResolution: string
{
    case Hour = 'hour';
    case Day = 'day';
    case Month = 'month';
    case Year = 'year';
}
