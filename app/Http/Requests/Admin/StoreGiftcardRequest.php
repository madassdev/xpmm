<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreGiftcardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'available_regions' => array_values(array_filter((array) $this->input('available_regions', []))),
            'available_values' => array_values(array_filter((array) $this->input('available_values', []))),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'rate' => ['required', 'numeric', 'min:0'],
            'available_regions' => ['nullable', 'array'],
            'available_regions.*' => ['string', 'max:5'],
            'available_values' => ['nullable', 'array'],
            'available_values.*' => ['numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }
}

