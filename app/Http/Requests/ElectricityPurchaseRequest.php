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
        return false;
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
