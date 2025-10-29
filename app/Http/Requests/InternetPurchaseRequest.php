<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InternetPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider'     => 'required|string|max:50',
            'account'      => ['required','string','max:64'],
            'planId'       => ['required','string','max:64'],
            'pin'          => ['required','string','regex:/^[0-9]{4,6}$/'],
            'callback_url' => 'nullable|url',
            'reference'    => 'nullable|string|max:250',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'provider' => strtolower(trim((string) $this->input('provider'))),
            'account'  => trim((string) $this->input('account')),
        ]);
    }
}
