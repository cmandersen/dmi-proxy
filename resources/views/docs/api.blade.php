@extends('layouts.app')

@section('title', 'API Documentation - DMI Weather API')

@section('content')
<h1 style="margin-bottom: 10px;">API Documentation</h1>
<p style="color: #666; margin-bottom: 30px;">
    This API provides access to current weather, forecasts, and historical weather data from the Danish Meteorological Institute (DMI).
</p>

<div class="endpoint">
    <div class="endpoint-header">
        <span class="method get">GET</span>
        <span class="endpoint-path">/api/v1/weather/current/{location}</span>
    </div>
    <p class="endpoint-description">
        Get current weather conditions for a specified location in Denmark.
    </p>

    <h3>Path Parameters</h3>
    <table>
        <thead>
            <tr>
                <th>Parameter</th>
                <th>Type</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>location</code></td>
                <td>string</td>
                <td>City name, postal code, or coordinates (lat,lon)</td>
            </tr>
        </tbody>
    </table>

    <h3>Query Parameters</h3>
    <table>
        <thead>
            <tr>
                <th>Parameter</th>
                <th>Type</th>
                <th>Required</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>parameters</code></td>
                <td>array</td>
                <td><span class="badge optional">Optional</span></td>
                <td>Weather parameters to include: <code>temperature</code>, <code>humidity</code>, <code>wind</code>, <code>pressure</code></td>
            </tr>
        </tbody>
    </table>

    <h3>Example Request</h3>
    <pre><code>GET {{ route('api.v1.weather.current', ['location' => 'copenhagen']) }}</code></pre>

    <h3>Example Response</h3>
    <pre><code>{
  "location": {
    "name": "Copenhagen",
    "coordinates": {
      "lat": 55.6761,
      "lon": 12.5683
    },
    "station": "Copenhagen"
  },
  "timestamp": "2025-01-15T12:00:00+00:00",
  "temperature": {
    "value": 15.2,
    "unit": "celsius"
  },
  "humidity": 65.0,
  "wind": {
    "speed": 5.5,
    "direction": 180.0,
    "unit": "meters_per_second",
    "chill": 3.2,
    "chill_unit": "celsius"
  },
  "pressure": {
    "value": 1013.25,
    "unit": "hectopascals"
  }
}</code></pre>

<p style="color: #666; margin-top: 15px;">
    <strong>Note:</strong> Wind chill is automatically calculated and included when temperature is ≤10°C and wind speed is >1.3 m/s.
</p>
</div>

<div class="endpoint">
    <div class="endpoint-header">
        <span class="method get">GET</span>
        <span class="endpoint-path">/api/v1/weather/forecast/{location}</span>
    </div>
    <p class="endpoint-description">
        Get weather forecast for a specified location in Denmark.
    </p>

    <h3>Path Parameters</h3>
    <table>
        <thead>
            <tr>
                <th>Parameter</th>
                <th>Type</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>location</code></td>
                <td>string</td>
                <td>City name, postal code, or coordinates (lat,lon)</td>
            </tr>
        </tbody>
    </table>

    <h3>Query Parameters</h3>
    <table>
        <thead>
            <tr>
                <th>Parameter</th>
                <th>Type</th>
                <th>Required</th>
                <th>Default</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>hours</code></td>
                <td>integer</td>
                <td><span class="badge optional">Optional</span></td>
                <td>48</td>
                <td>Number of hours to forecast (1-72)</td>
            </tr>
        </tbody>
    </table>

    <h3>Example Request</h3>
    <pre><code>GET {{ route('api.v1.weather.forecast', ['location' => 'aarhus']) }}?hours=24</code></pre>

    <h3>Example Response</h3>
    <pre><code>{
  "location": {
    "name": "aarhus",
    "coordinates": {
      "lat": 56.1629,
      "lon": 10.2039
    }
  },
  "generated_at": "2025-01-15T12:00:00+00:00",
  "forecast": [
    {
      "timestamp": "2025-01-15T13:00:00+00:00",
      "temperature": 14.5,
      "wind_speed": 6.0,
      "wind_direction": 190.0,
      "wind_chill": 3.8,
      "precipitation": 0.5,
      "cloud_cover": 50.0
    }
  ]
}</code></pre>

<p style="color: #666; margin-top: 15px;">
    <strong>Note:</strong> Wind chill is automatically calculated and included in forecast data when conditions meet the criteria (temperature ≤10°C and wind speed >1.3 m/s).
</p>
</div>

<div class="endpoint">
    <div class="endpoint-header">
        <span class="method get">GET</span>
        <span class="endpoint-path">/api/v1/weather/historical/{location}</span>
    </div>
    <p class="endpoint-description">
        Get historical weather data for a specified location in Denmark.
    </p>

    <h3>Path Parameters</h3>
    <table>
        <thead>
            <tr>
                <th>Parameter</th>
                <th>Type</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>location</code></td>
                <td>string</td>
                <td>City name, postal code, or coordinates (lat,lon)</td>
            </tr>
        </tbody>
    </table>

    <h3>Query Parameters</h3>
    <table>
        <thead>
            <tr>
                <th>Parameter</th>
                <th>Type</th>
                <th>Required</th>
                <th>Default</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>from</code></td>
                <td>date</td>
                <td><span class="badge required">Required</span></td>
                <td>-</td>
                <td>Start date (YYYY-MM-DD)</td>
            </tr>
            <tr>
                <td><code>to</code></td>
                <td>date</td>
                <td><span class="badge required">Required</span></td>
                <td>-</td>
                <td>End date (YYYY-MM-DD)</td>
            </tr>
            <tr>
                <td><code>resolution</code></td>
                <td>string</td>
                <td><span class="badge optional">Optional</span></td>
                <td>day</td>
                <td>Time resolution: <code>hour</code>, <code>day</code>, <code>month</code>, <code>year</code></td>
            </tr>
            <tr>
                <td><code>parameters</code></td>
                <td>array</td>
                <td><span class="badge optional">Optional</span></td>
                <td>-</td>
                <td>Weather parameters: <code>temperature</code>, <code>precipitation</code>, <code>wind</code>, <code>humidity</code>, <code>sunshine</code></td>
            </tr>
        </tbody>
    </table>

    <h3>Example Request</h3>
    <pre><code>GET {{ route('api.v1.weather.historical', ['location' => 'odense']) }}?from=2024-01-01&to=2024-01-31&resolution=day</code></pre>

    <h3>Example Response</h3>
    <pre><code>{
  "location": {
    "name": "odense",
    "coordinates": {
      "lat": 55.4038,
      "lon": 10.4024
    },
    "station": "Odense"
  },
  "period": {
    "from": "2024-01-01T00:00:00+00:00",
    "to": "2024-01-31T00:00:00+00:00",
    "resolution": "day"
  },
  "data": [
    {
      "timestamp": "2024-01-01T00:00:00+00:00",
      "temperature": 5.5,
      "precipitation": 2.3,
      "wind_speed": 4.5,
      "wind_chill": 1.2
    }
  ]
}</code></pre>

<p style="color: #666; margin-top: 15px;">
    <strong>Note:</strong> Wind chill is automatically calculated and included in historical data when conditions meet the criteria (temperature ≤10°C and wind speed >1.3 m/s).
</p>
</div>

<h2 style="margin-top: 50px;">Wind Chill Calculation</h2>
<p style="color: #666; margin-bottom: 15px;">
    Wind chill is automatically calculated using the North American and UK wind chill index formula. It represents how cold the air feels on exposed skin due to the combined effect of temperature and wind.
</p>

<h3>When Wind Chill is Included</h3>
<p style="color: #666; margin-bottom: 15px;">
    Wind chill values are only calculated and included when:
</p>
<ul style="margin-left: 20px; color: #666; margin-bottom: 20px;">
    <li style="margin-bottom: 8px;"><strong>Temperature</strong> is at or below 10°C</li>
    <li style="margin-bottom: 8px;"><strong>Wind speed</strong> is above 1.3 m/s (4.68 km/h)</li>
</ul>

<p style="color: #666; margin-bottom: 15px;">
    If conditions don't meet these criteria, the wind chill fields will be omitted from the response.
</p>

<h3>Wind Chill Formula</h3>
<pre><code>Wind Chill (°C) = 13.12 + 0.6215T - 11.37V^0.16 + 0.3965TV^0.16

Where:
  T = Air temperature (°C)
  V = Wind speed (km/h)</code></pre>

<h2 style="margin-top: 50px;">Location Formats</h2>
<p style="color: #666; margin-bottom: 15px;">
    The API accepts locations in the following formats:
</p>
<table>
    <thead>
        <tr>
            <th>Format</th>
            <th>Example</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>City Name</td>
            <td><code>copenhagen</code>, <code>københavn</code>, <code>aarhus</code></td>
            <td>Danish city names (case-insensitive)</td>
        </tr>
        <tr>
            <td>Postal Code</td>
            <td><code>1000</code>, <code>8000</code>, <code>5000</code></td>
            <td>4-digit Danish postal codes</td>
        </tr>
        <tr>
            <td>Coordinates</td>
            <td><code>55.6761,12.5683</code></td>
            <td>Latitude and longitude (comma-separated)</td>
        </tr>
    </tbody>
</table>

<h2 style="margin-top: 50px;">Error Responses</h2>
<p style="color: #666; margin-bottom: 15px;">
    The API returns standard HTTP status codes and JSON error responses:
</p>

<h3>400 Bad Request</h3>
<pre><code>{
  "error": "Unable to geocode location: unknowncity",
  "location": "unknowncity"
}</code></pre>

<h3>422 Unprocessable Entity</h3>
<pre><code>{
  "message": "The from field is required.",
  "errors": {
    "from": [
      "The from field is required."
    ]
  }
}</code></pre>

@php
    $currentTtl = config('services.dmi.cache_ttl.current');
    $forecastTtl = config('services.dmi.cache_ttl.forecast');
    $historicalTtl = config('services.dmi.cache_ttl.historical', 86400);
    $geocodingTtl = config('services.dmi.cache_ttl.geocoding', 86400);

    $formatTtl = function($seconds) {
        if ($seconds >= 3600) {
            $hours = floor($seconds / 3600);
            return $hours . ' ' . ($hours === 1 ? 'hour' : 'hours');
        }
        if ($seconds >= 60) {
            $minutes = floor($seconds / 60);
            return $minutes . ' ' . ($minutes === 1 ? 'minute' : 'minutes');
        }
        return $seconds . ' ' . ($seconds === 1 ? 'second' : 'seconds');
    };
@endphp

<h2 style="margin-top: 50px;">Rate Limiting & Caching</h2>
<ul style="margin-left: 20px; color: #666;">
    <li style="margin-bottom: 8px;">Current weather data is cached for <strong>{{ $formatTtl($currentTtl) }}</strong></li>
    <li style="margin-bottom: 8px;">Forecast data is cached for <strong>{{ $formatTtl($forecastTtl) }}</strong></li>
    <li style="margin-bottom: 8px;">Historical data is cached for <strong>{{ $formatTtl($historicalTtl) }}</strong></li>
    <li style="margin-bottom: 8px;">Geocoding results are cached for <strong>{{ $formatTtl($geocodingTtl) }}</strong></li>
</ul>

@endsection
