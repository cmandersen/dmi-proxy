<?php

namespace App\Http\Requests\Api;

use App\Enums\HistoricalWeatherParameter;
use App\Enums\TimeResolution;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HistoricalWeatherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'resolution' => $this->input('resolution', 'day'),
            'parameters' => $this->input('parameters', []),
        ]);
    }

    public function rules(): array
    {
        return [
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after:from'],
            'resolution' => ['sometimes', Rule::enum(TimeResolution::class)],
            'parameters' => ['sometimes', 'array'],
            'parameters.*' => [Rule::enum(HistoricalWeatherParameter::class)],
        ];
    }
}
