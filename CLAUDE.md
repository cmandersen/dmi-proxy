# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 API application that provides weather data for Denmark via the DMI (Danish Meteorological Institute) Open Data API. The application includes:

- Current weather conditions
- Weather forecasts (up to 48 hours)
- Historical weather data
- Location geocoding using DAWA (Danish Address Web API)

## Key Architecture Patterns

### Action-Based Architecture
The codebase uses single-responsibility Action classes for discrete operations:
- **app/Actions/Weather/** - Weather-related calculations (e.g., `CalculateWindChill`)
- **app/Actions/Dawa/** - Location data fetching from DAWA API (e.g., `FetchMunicipalities`, `FetchPostalCodes`)

Actions follow the pattern: `execute()` method with clear input/output contracts.

### Data Transfer Objects (DTOs)
All data structures use Spatie's Laravel Data package for type-safe DTOs:
- `WeatherDataDTO` - Current weather response
- `ForecastDataDTO` - Forecast response
- `HistoricalWeatherDTO` - Historical data response
- Supporting DTOs: `LocationData`, `CoordinatesData`, `TemperatureData`, `WindData`, `PressureData`, `PeriodData`

### Service Layer
Services coordinate between actions, external APIs, and data transformation:
- **DmiWeatherService** (`app/Services/Weather/`) - Main service for DMI API integration with caching
- **WeatherTransformer** - Transforms DMI API responses to DTOs
- **GeocodingService** (`app/Services/Location/`) - Handles location name to coordinates conversion

### HTTP Macros for External APIs
Custom HTTP macros are defined in `AppServiceProvider:54` for DMI API endpoints:
- `Http::dmiClimate()` - Climate data API with retry logic
- `Http::dmiMetObs()` - Meteorological observations API
- `Http::dmiForecast()` - Forecast API

All macros include automatic retries, timeouts, and required API keys from config.

### API Versioning
API routes use version prefixing (`/api/v1/`). Controllers are organized under `app/Http/Controllers/Api/V1/`.

### Enums for Domain Values
Weather parameters and time resolutions are defined as enums:
- `WeatherParameter` - Current weather parameters
- `HistoricalWeatherParameter` - Historical data parameters
- `TimeResolution` - Time granularity for queries

## Development Commands

### Running the Application
```bash
composer run dev  # Runs server, queue, logs (Pail), and Vite concurrently
php artisan serve  # Run server only
```

### Testing
```bash
composer run test              # Run all tests with config clear
php artisan test               # Run all tests
php artisan test tests/Feature/WeatherApiTest.php  # Run specific test file
php artisan test --filter=testCurrentWeather       # Run specific test
```

### Code Formatting
```bash
vendor/bin/pint --dirty  # Format changed files only (run before finalizing)
vendor/bin/pint          # Format all files
```

### Project Setup (First Time)
```bash
composer run setup  # Install dependencies, copy .env, generate key, migrate, build assets
```

## Configuration Notes

### DMI API Keys
The application requires three DMI API keys (configured in `.env`):
- `DMI_CLIMATE_KEY` - Climate data API
- `DMI_METOBS_KEY` - Meteorological observations API
- `DMI_FORECAST_KEY` - Forecast API

Keys are accessed via `config('services.dmi.*')` - never use `env()` directly outside config files.

### Caching Strategy
Weather data is cached in `DmiWeatherService:21-50` with TTLs defined in `config/services.php`:
- Current weather: 5 minutes (300s)
- Forecast: 30 minutes (1800s)
- Historical: 24 hours (86400s)

Cache keys include location coordinates for proper invalidation.

### Database
Uses SQLite by default (`DB_CONNECTION=sqlite`). Database file should exist at `database/database.sqlite`.

### Queue Configuration
Queue driver is set to `database`. Long-running operations should use queued jobs with `ShouldQueue`.

## Important Conventions

### Form Requests
All API endpoints use Form Request validation (e.g., `CurrentWeatherRequest`, `ForecastRequest`). Check sibling requests to match validation rule format (array vs string based).

### Exception Handling
Custom `WeatherServiceException` is rendered as JSON for API routes in `bootstrap/app.php:17-24`.

### Testing Strategy
PHPUnit is used for all tests. Tests are organized into:
- `tests/Feature/` - API endpoint and integration tests
- `tests/Unit/` - Unit tests for isolated logic

Run affected tests after each change using `--filter` for speed.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3.25
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11


## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== phpunit/core rules ===

## PHPUnit Core

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit <name>` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files, these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.
</laravel-boost-guidelines>
