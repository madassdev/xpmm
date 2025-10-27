<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AirtimePurchaseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'network' => 'required|string|in:mtn,airtel,glo,9mobile',
            'phone'   => ['required','string','min:10','max:20','regex:/^[0-9+]+$/'],
            'amount'  => 'required|integer|min:50',
            'pin'     => ['required','string','regex:/^[0-9]{4,6}$/'], // 4–6 digits
            // optional passthroughs if you ever add them on FE
            'ported'       => 'nullable|boolean',
            'callback_url' => 'nullable|url',
            'reference'    => 'nullable|string|max:250',
        ];
    }

    public function prepareForValidation(): void
    {
        // Trim and normalize obvious whitespace cruft
        $this->merge([
            'network' => strtolower(trim((string) $this->input('network'))),
            'phone'   => trim((string) $this->input('phone')),
        ]);
    }
}
