<?php

namespace App\Enums;

enum HistoricalWeatherParameter: string
{
    case Temperature = 'temperature';
    case Precipitation = 'precipitation';
    case Wind = 'wind';
    case Humidity = 'humidity';
    case Sunshine = 'sunshine';
}
