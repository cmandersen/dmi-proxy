<?php

namespace Tests\Unit\Actions\Weather;

use App\Actions\Weather\CalculateWindChill;
use PHPUnit\Framework\TestCase;

class CalculateWindChillTest extends TestCase
{
    private CalculateWindChill $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new CalculateWindChill;
    }

    public function test_calculates_wind_chill_with_valid_conditions(): void
    {
        // Temperature: 0°C, Wind: 5 m/s (18 km/h)
        $result = $this->calculator->calculate(0.0, 5.0);

        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(-4.9, $result, 0.1);
    }

    public function test_calculates_wind_chill_at_minus_ten_degrees(): void
    {
        // Temperature: -10°C, Wind: 10 m/s (36 km/h)
        $result = $this->calculator->calculate(-10.0, 10.0);

        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(-20.0, $result, 0.5);
    }

    public function test_returns_null_when_temperature_is_null(): void
    {
        $result = $this->calculator->calculate(null, 5.0);

        $this->assertNull($result);
    }

    public function test_returns_null_when_wind_speed_is_null(): void
    {
        $result = $this->calculator->calculate(0.0, null);

        $this->assertNull($result);
    }

    public function test_returns_null_when_both_are_null(): void
    {
        $result = $this->calculator->calculate(null, null);

        $this->assertNull($result);
    }

    public function test_returns_null_when_temperature_is_above_ten_celsius(): void
    {
        // Temperature: 15°C, Wind: 5 m/s - too warm for wind chill
        $result = $this->calculator->calculate(15.0, 5.0);

        $this->assertNull($result);
    }

    public function test_returns_null_when_temperature_is_exactly_above_ten_celsius(): void
    {
        // Temperature: 10.1°C - just above threshold
        $result = $this->calculator->calculate(10.1, 5.0);

        $this->assertNull($result);
    }

    public function test_calculates_wind_chill_when_temperature_is_exactly_ten_celsius(): void
    {
        // Temperature: 10°C, Wind: 5 m/s - should calculate
        $result = $this->calculator->calculate(10.0, 5.0);

        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(7.6, $result, 0.2);
    }

    public function test_returns_null_when_wind_speed_is_too_low(): void
    {
        // Temperature: 0°C, Wind: 1 m/s (3.6 km/h) - below 4.8 km/h threshold
        $result = $this->calculator->calculate(0.0, 1.0);

        $this->assertNull($result);
    }

    public function test_returns_null_when_wind_speed_is_exactly_at_threshold(): void
    {
        // 4.8 km/h = 1.333... m/s - at threshold, should return null
        $result = $this->calculator->calculate(0.0, 1.333);

        $this->assertNull($result);
    }

    public function test_calculates_wind_chill_when_wind_speed_is_just_above_threshold(): void
    {
        // 5.0 km/h = 1.39 m/s - just above threshold
        $result = $this->calculator->calculate(0.0, 1.39);

        $this->assertNotNull($result);
    }

    public function test_returns_rounded_value_to_one_decimal(): void
    {
        $result = $this->calculator->calculate(5.0, 10.0);

        $this->assertNotNull($result);
        // Check that result has at most 1 decimal place
        $this->assertEquals($result, round($result, 1));
    }

    public function test_calculates_wind_chill_with_realistic_danish_winter_conditions(): void
    {
        // Copenhagen winter: 2°C, 20 km/h wind (5.56 m/s)
        $result = $this->calculator->calculate(2.0, 5.56);

        $this->assertNotNull($result);
        $this->assertLessThan(2.0, $result); // Wind chill should be colder than actual temp
        $this->assertEqualsWithDelta(-2.7, $result, 0.2);
    }

    public function test_wind_chill_is_always_colder_than_actual_temperature(): void
    {
        $temperature = 5.0;
        $windSpeed = 8.0;

        $result = $this->calculator->calculate($temperature, $windSpeed);

        $this->assertNotNull($result);
        $this->assertLessThan($temperature, $result);
    }

    public function test_higher_wind_speed_produces_colder_wind_chill(): void
    {
        $temperature = 0.0;

        $windChill5ms = $this->calculator->calculate($temperature, 5.0);
        $windChill10ms = $this->calculator->calculate($temperature, 10.0);

        $this->assertNotNull($windChill5ms);
        $this->assertNotNull($windChill10ms);
        $this->assertLessThan($windChill5ms, $windChill10ms);
    }
}
