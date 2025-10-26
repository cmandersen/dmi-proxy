<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ForecastRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'hours' => $this->input('hours', 48),
        ]);
    }

    public function rules(): array
    {
        return [
            'hours' => ['sometimes', 'integer', 'min:1', 'max:72'],
        ];
    }
}
