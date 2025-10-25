<?php

namespace App\Actions\Weather;

class CalculateWindChill
{
    /**
     * Calculate wind chill using the North American/JAG/TI formula (2001).
     *
     * Wind chill is only applicable when:
     * - Temperature ≤ 10°C
     * - Wind speed > 4.8 km/h (1.33 m/s)
     *
     * Formula: WC = 13.12 + 0.6215×T - 11.37×V^0.16 + 0.3965×T×V^0.16
     * Where: T = air temperature (°C), V = wind speed (km/h)
     */
    public function calculate(?float $temperatureCelsius, ?float $windSpeedMetersPerSecond): ?float
    {
        if ($temperatureCelsius === null || $windSpeedMetersPerSecond === null) {
            return null;
        }

        // Convert m/s to km/h
        $windSpeedKmh = $windSpeedMetersPerSecond * 3.6;

        // Wind chill only applies when temp ≤ 10°C and wind > 4.8 km/h
        if ($temperatureCelsius > 10.0 || $windSpeedKmh <= 4.8) {
            return null;
        }

        $t = $temperatureCelsius;
        $v = $windSpeedKmh;

        // North American wind chill formula (2001)
        $windChill = 13.12
            + (0.6215 * $t)
            - (11.37 * pow($v, 0.16))
            + (0.3965 * $t * pow($v, 0.16));

        return round($windChill, 1);
    }
}
