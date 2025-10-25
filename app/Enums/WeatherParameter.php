<?php

namespace App\Enums;

enum WeatherParameter: string
{
    case Temperature = 'temperature';
    case Humidity = 'humidity';
    case Wind = 'wind';
    case Pressure = 'pressure';
}
