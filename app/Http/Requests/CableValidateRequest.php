<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CableValidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider'  => 'required|string|max:50',
            'smartcard' => ['required','string','max:32'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'provider'  => strtolower(trim((string) $this->input('provider'))),
            'smartcard' => preg_replace('/\s+/', '', (string) $this->input('smartcard')),
        ]);
    }
}
