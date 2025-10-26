# DMI Weather API

A Laravel-based proxy API for the Danish Meteorological Institute (DMI) weather data services. This API provides a clean, RESTful interface to access current weather conditions, forecasts, and historical weather data for locations across Denmark.

## Features

-   **Current Weather**: Real-time weather observations from DMI weather stations
-   **Weather Forecasts**: Up to 72 hours of forecast data
-   **Historical Data**: Access to historical weather records with flexible time resolutions
-   **Automatic Wind Chill**: Calculated and included when conditions meet criteria (≤10°C, >1.3 m/s wind)
-   **Smart Geocoding**: Support for city names, postal codes, and coordinates with caching
-   **Response Caching**: Configurable cache durations for optimal performance

## Requirements

-   PHP 8.3+
-   Composer
-   DMI API Keys (Climate, MetObs, and Forecast)

## Installation

1. Clone the repository:

```bash
git clone https://github.com/cmandersen/dmi-proxy.git
cd dmi-proxy
```

2. Install dependencies:

```bash
composer install
```

3. Copy the environment file and configure:

```bash
cp .env.example .env
```

4. Generate application key:

```bash
php artisan key:generate
```

5. Configure your DMI API keys in `.env`:

```env
DMI_CLIMATE_KEY=your_climate_api_key
DMI_METOBS_KEY=your_metobs_api_key
DMI_FORECAST_KEY=your_forecast_api_key
```

6. (Optional) Configure cache TTL values:

```env
DMI_CACHE_CURRENT=300        # 5 minutes
DMI_CACHE_FORECAST=1800      # 30 minutes
DMI_CACHE_HISTORICAL=86400   # 24 hours
DMI_CACHE_GEOCODING=86400    # 24 hours
```

7. Cache Danish cities for faster geocoding:

```bash
php artisan weather:cache-cities
```

## Usage

Start the development server:

```bash
php artisan serve
```

Visit the API documentation at `http://localhost:8000`

### API Endpoints

#### Get Current Weather

```http
GET /api/weather/current/{location}
```

**Parameters:**

-   `location` (path): City name, postal code, or coordinates (lat,lon)
-   `parameters` (query, optional): Array of parameters to include (`temperature`, `humidity`, `wind`, `pressure`)

**Example:**

```bash
curl http://localhost:8000/api/weather/current/copenhagen
```

#### Get Forecast

```http
GET /api/weather/forecast/{location}
```

**Parameters:**

-   `location` (path): City name, postal code, or coordinates
-   `hours` (query, optional): Number of hours to forecast (1-72, default: 48)

**Example:**

```bash
curl http://localhost:8000/api/weather/forecast/aarhus?hours=24
```

#### Get Historical Weather

```http
GET /api/weather/historical/{location}
```

**Parameters:**

-   `location` (path): City name, postal code, or coordinates
-   `from` (query, required): Start date (YYYY-MM-DD)
-   `to` (query, required): End date (YYYY-MM-DD)
-   `resolution` (query, optional): Time resolution (`hour`, `day`, `month`, `year`, default: `day`)
-   `parameters` (query, optional): Array of parameters (`temperature`, `precipitation`, `wind`, `humidity`, `sunshine`)

**Example:**

```bash
curl "http://localhost:8000/api/weather/historical/odense?from=2024-01-01&to=2024-01-31&resolution=day"
```

## Testing

Run the test suite:

```bash
php artisan test
```

Run specific tests:

```bash
php artisan test --filter=WeatherControllerTest
```

The project includes 68 tests covering:

-   Unit tests for services and transformers
-   Feature tests for API endpoints
-   Wind chill calculation tests
-   Geocoding service tests

## Code Formatting

This project uses Laravel Pint for code formatting:

```bash
vendor/bin/pint
```

## Project Structure

```
app/
├── Actions/          # Reusable action classes
│   └── Dawa/        # Danish Address Web API integrations
├── Console/
│   └── Commands/    # Artisan commands
├── DTOs/            # Data Transfer Objects
├── Enums/           # Validation enums
├── Exceptions/      # Custom exceptions
├── Http/
│   ├── Controllers/
│   │   └── Api/     # API controllers
│   └── Requests/
│       └── Api/     # Form request validation
└── Services/        # Business logic services
    ├── Location/    # Geocoding services
    └── Weather/     # Weather data services
```

## Wind Chill Calculation

Wind chill is automatically calculated using the North American and UK wind chill index formula when:

-   Temperature is at or below 10°C
-   Wind speed is above 1.3 m/s (4.68 km/h)

Formula:

```
Wind Chill (°C) = 13.12 + 0.6215T - 11.37V^0.16 + 0.3965TV^0.16

Where:
  T = Air temperature (°C)
  V = Wind speed (km/h)
```

## Cache Management

The API implements intelligent caching:

-   **Current weather**: 5 minutes (configurable)
-   **Forecast data**: 30 minutes (configurable)
-   **Historical data**: 24 hours (configurable)
-   **Geocoding results**: 24 hours (configurable)

Clear all caches:

```bash
php artisan cache:clear
```

## Credits

-   Weather data provided by [Danish Meteorological Institute (DMI)](https://www.dmi.dk/)
-   Geocoding powered by [DAWA (Danish Address Web API)](https://dawadocs.dataforsyningen.dk/)
