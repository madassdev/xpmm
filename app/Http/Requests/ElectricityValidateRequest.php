<?php

namespace App\Http\Requests;

use App\Support\ElectricityDiscoMap;
use Illuminate\Foundation\Http\FormRequest;

class ElectricityValidateRequest extends FormRequest
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
        $this->merge([
            'disco'    => ElectricityDiscoMap::normalize((string) $this->input('disco')),
            'meter_no' => preg_replace('/\s+/', '', (string) $this->input('meter_no')),
            'type'     => strtolower((string) $this->input('type')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {
        return [
            'disco'      => 'required|string|max:50',     // e.g., IBEDC, EKO, AEDC...
            'meter_no'   => 'required|string|min:5|max:32',
            'type'       => 'required|in:prepaid,postpaid',
        ];
    }
}
