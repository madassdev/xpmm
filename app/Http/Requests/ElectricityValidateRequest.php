<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ElectricityValidateRequest extends FormRequest
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
            'disco'      => 'required|string|max:50',     // e.g., IBEDC, EKO, AEDC...
            'meter_no'   => 'required|string|max:32',
            'type'       => 'required|in:prepaid,postpaid',
        ];
    }
}
