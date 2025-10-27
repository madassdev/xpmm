<?php

namespace App\Http\Requests\Admin;

use App\Models\Wallet;
use Illuminate\Foundation\Http\FormRequest;

class ManualWalletFundingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('fund', Wallet::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['required', 'string', 'max:255', 'unique:wallet_transactions,reference'],
            'description' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'size:3'],
        ];
    }
}
