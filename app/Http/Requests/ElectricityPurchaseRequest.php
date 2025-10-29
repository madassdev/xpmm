<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ElectricityPurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {
        return [
            'disco'        => 'required|string|max:50',
            'meter_no'     => 'required|string|max:32',
            'type'         => 'required|in:prepaid,postpaid',
            'amount'       => 'required|integer|min:100',
            'pin'          => ['required','string','regex:/^[0-9]{4,6}$/'],
            'callback_url' => 'nullable|url',
            'reference'    => 'nullable|string|max:250',
        ];
    }

    public function prepareForValidation(): void
    {
        $raw = strtolower(trim((string) $this->input('disco')));
        $normalized = str_replace([' electricity', '-electricity'], '', $raw);
        $normalized = str_replace(' ', '', $normalized);

        $this->merge([
            'disco'    => $normalized,
            'meter_no' => preg_replace('/\s+/', '', (string) $this->input('meter_no')),
            'type'     => strtolower(trim((string) $this->input('type'))),
        ]);
    }
}
