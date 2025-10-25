<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\WeatherParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CurrentWeatherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'parameters' => $this->input('parameters', []),
        ]);
    }

    public function rules(): array
    {
        return [
            'parameters' => ['sometimes', 'array'],
            'parameters.*' => [Rule::enum(WeatherParameter::class)],
        ];
    }
}
