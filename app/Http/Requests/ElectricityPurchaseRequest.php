<?php

namespace App\Http\Requests;

use App\Support\ElectricityDiscoMap;
use Illuminate\Foundation\Http\FormRequest;

class ElectricityPurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function prepareForValidation(): void
    {
        $amount = preg_replace('/[^0-9]/', '', (string) $this->input('amount'));

        $this->merge([
            'disco'        => ElectricityDiscoMap::normalize((string) $this->input('disco')),
            'meter_no'     => preg_replace('/\s+/', '', (string) $this->input('meter_no')),
            'type'         => strtolower((string) $this->input('type')),
            'amount'       => $amount !== '' ? (int) $amount : $amount,
            'callback_url' => $this->input('callback_url') ? trim((string) $this->input('callback_url')) : null,
            'reference'    => $this->input('reference') ? trim((string) $this->input('reference')) : null,
        ]);
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
            'callback_url' => 'nullable|url',
            'reference'    => 'nullable|string|max:250',
        ];
    }
}
