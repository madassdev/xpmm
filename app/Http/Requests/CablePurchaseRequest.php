<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CablePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider'     => 'required|string|max:50',
            'smartcard'    => ['required','string','max:32'],
            'planId'       => ['required','string','max:64'],
            'phone'        => ['nullable','string','max:20'],
            'pin'          => ['required','string','regex:/^[0-9]{4,6}$/'],
            'callback_url' => 'nullable|url',
            'reference'    => 'nullable|string|max:250',
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
