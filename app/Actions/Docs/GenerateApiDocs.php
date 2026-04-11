<?php

namespace App\Actions\Docs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;

class GenerateApiDocs
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(): array
    {
        $endpoints = [];

        foreach (RouteFacade::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/weather')) {
                continue;
            }

            $endpoints[] = $this->buildEndpoint($route);
        }

        return $endpoints;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildEndpoint(Route $route): array
    {
        $method = strtoupper(collect($route->methods())->first(fn ($m) => $m !== 'HEAD'));
        $formRequest = $this->resolveFormRequest($route);
        $rules = $formRequest ? $formRequest->rules() : [];
        $defaults = $formRequest ? $this->extractDefaults($formRequest) : [];

        return [
            'method' => $method,
            'uri' => '/'.$route->uri(),
            'summary' => $this->generateSummary($route),
            'description' => $this->generateDescription($route),
            'path_parameters' => $this->extractPathParameters($route),
            'query_parameters' => $this->buildQueryParameters($rules, $defaults),
            'example_response' => $this->getExampleResponse($route),
        ];
    }

    private function resolveFormRequest(Route $route): ?FormRequest
    {
        $controller = $route->getController();
        $method = $route->getActionMethod();
        $reflection = new \ReflectionMethod($controller, $method);

        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof \ReflectionNamedType && is_subclass_of($type->getName(), FormRequest::class)) {
                return new ($type->getName());
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractDefaults(FormRequest $formRequest): array
    {
        $defaults = [];
        $reflection = new \ReflectionMethod($formRequest, 'prepareForValidation');
        $source = file_get_contents($reflection->getFileName());

        if (preg_match_all("/\\\$this->input\('(\w+)',\s*(.+?)\)/", $source, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $value = trim($match[2]);

                if ($value === '[]') {
                    continue;
                }

                if (is_numeric($value)) {
                    $defaults[$match[1]] = (int) $value;
                } else {
                    $defaults[$match[1]] = trim($value, "'\"");
                }
            }
        }

        return $defaults;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function extractPathParameters(Route $route): array
    {
        $params = [];

        foreach ($route->parameterNames() as $name) {
            $params[] = [
                'name' => $name,
                'type' => 'string',
                'description' => $this->describePathParam($name),
            ];
        }

        return $params;
    }

    /**
     * @param  array<string, mixed>  $rules
     * @param  array<string, mixed>  $defaults
     * @return array<int, array<string, mixed>>
     */
    private function buildQueryParameters(array $rules, array $defaults): array
    {
        $params = [];

        foreach ($rules as $field => $fieldRules) {
            if (str_ends_with($field, '.*')) {
                continue;
            }

            $fieldRules = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;
            $required = in_array('required', $fieldRules);
            $type = $this->inferType($fieldRules);
            $constraints = $this->extractConstraints($fieldRules);

            $wildcardRules = $rules[$field.'.*'] ?? [];
            $wildcardRules = is_string($wildcardRules) ? explode('|', $wildcardRules) : $wildcardRules;
            $enum = $this->extractEnum($wildcardRules) ?? $this->extractEnum($fieldRules);

            $param = [
                'name' => $field === 'parameters' ? 'parameters[]' : $field,
                'type' => $type,
                'required' => $required,
                'description' => $this->describeQueryParam($field),
                'enum' => $enum,
            ];

            if (isset($defaults[$field])) {
                $param['default'] = $defaults[$field];
            }

            if (isset($constraints['min'])) {
                $param['min'] = $constraints['min'];
            }

            if (isset($constraints['max'])) {
                $param['max'] = $constraints['max'];
            }

            $params[] = $param;
        }

        return $params;
    }

    private function inferType(array $rules): string
    {
        foreach ($rules as $rule) {
            if (! is_string($rule)) {
                continue;
            }

            if (in_array($rule, ['integer', 'date', 'array'])) {
                return $rule;
            }
        }

        return 'string';
    }

    /**
     * @return string[]|null
     */
    private function extractEnum(array $rules): ?array
    {
        foreach ($rules as $rule) {
            if ($rule instanceof Enum) {
                $reflection = new \ReflectionClass($rule);
                $typeProperty = $reflection->getProperty('type');
                $enumClass = $typeProperty->getValue($rule);

                return array_map(fn ($case) => $case->value, $enumClass::cases());
            }
        }

        return null;
    }

    /**
     * @return array<string, int>
     */
    private function extractConstraints(array $rules): array
    {
        $constraints = [];

        foreach ($rules as $rule) {
            if (! is_string($rule)) {
                continue;
            }

            if (str_starts_with($rule, 'min:')) {
                $constraints['min'] = (int) Str::after($rule, 'min:');
            }

            if (str_starts_with($rule, 'max:')) {
                $constraints['max'] = (int) Str::after($rule, 'max:');
            }
        }

        return $constraints;
    }

    private function describePathParam(string $name): string
    {
        return match ($name) {
            'location' => 'City name, postal code, or coordinates (lat,lon)',
            default => ucfirst($name),
        };
    }

    private function describeQueryParam(string $name): string
    {
        return match ($name) {
            'parameters' => 'Weather parameters to include. If omitted, all are returned.',
            'hours' => 'Number of hours to forecast.',
            'from' => 'Start date (YYYY-MM-DD)',
            'to' => 'End date (YYYY-MM-DD). Must be after from.',
            'resolution' => 'Time resolution for aggregation.',
            default => ucfirst($name),
        };
    }

    private function generateSummary(Route $route): string
    {
        return match ($route->getActionMethod()) {
            'current' => 'Current weather',
            'forecast' => 'Weather forecast',
            'historical' => 'Historical weather',
            default => Str::headline($route->getActionMethod()),
        };
    }

    private function generateDescription(Route $route): string
    {
        return match ($route->getActionMethod()) {
            'current' => 'Get current weather conditions for a location in Denmark.',
            'forecast' => 'Get weather forecast (up to 72 hours) for a location in Denmark.',
            'historical' => 'Get historical weather data for a location in Denmark.',
            default => '',
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getExampleResponse(Route $route): ?array
    {
        return match ($route->getActionMethod()) {
            'current' => [
                'location' => ['name' => 'Copenhagen', 'coordinates' => ['lat' => 55.6761, 'lon' => 12.5683], 'station' => 'Copenhagen'],
                'timestamp' => '2025-01-15T12:00:00+00:00',
                'temperature' => ['value' => 15.2, 'unit' => 'celsius'],
                'humidity' => 65.0,
                'wind' => ['speed' => 5.5, 'direction' => 180.0, 'unit' => 'meters_per_second', 'chill' => 3.2, 'chill_unit' => 'celsius'],
                'pressure' => ['value' => 1013.25, 'unit' => 'hectopascals'],
            ],
            'forecast' => [
                'location' => ['name' => 'aarhus', 'coordinates' => ['lat' => 56.1629, 'lon' => 10.2039]],
                'generated_at' => '2025-01-15T12:00:00+00:00',
                'forecast' => [
                    ['timestamp' => '2025-01-15T13:00:00+00:00', 'temperature' => 14.5, 'wind_speed' => 6.0, 'wind_direction' => 190.0, 'wind_chill' => 3.8, 'precipitation' => 0.5, 'cloud_cover' => 50.0],
                ],
            ],
            'historical' => [
                'location' => ['name' => 'odense', 'coordinates' => ['lat' => 55.4038, 'lon' => 10.4024], 'station' => 'Odense'],
                'period' => ['from' => '2024-01-01T00:00:00+00:00', 'to' => '2024-01-31T00:00:00+00:00', 'resolution' => 'day'],
                'data' => [
                    ['timestamp' => '2024-01-01T00:00:00+00:00', 'temperature' => 5.5, 'precipitation' => 2.3, 'wind_speed' => 4.5, 'wind_chill' => 1.2],
                ],
            ],
            default => null,
        };
    }
}
