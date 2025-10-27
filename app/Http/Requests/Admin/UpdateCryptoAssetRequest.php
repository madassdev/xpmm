<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCryptoAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $isActive = $this->input('is_active', $this->route('cryptoAsset')?->is_active ?? true);

        $this->merge([
            'symbol' => strtoupper((string) $this->input('symbol')),
            'sale_type' => strtolower((string) $this->input('sale_type')),
            'is_active' => filter_var($isActive, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['required', 'string', 'max:20'],
            'rate' => ['required', 'numeric', 'min:0'],
            'wallet_address' => ['required', 'string', 'max:255'],
            'sale_type' => ['required', 'in:buy,sell'],
            'charges' => ['required', 'numeric', 'min:0'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}

