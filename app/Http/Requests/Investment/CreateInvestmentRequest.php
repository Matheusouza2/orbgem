<?php

namespace App\Http\Requests\Investment;

use App\Enums\InvestmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateInvestmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'integer', 'exists:wallets,id'],
            'name' => ['required', 'string', 'max:255'],
            'ticker' => ['nullable', 'string', 'max:20'],
            'type' => ['required', Rule::enum(InvestmentType::class)],
            'institution' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.00000001'],
            'average_price' => ['required', 'integer', 'min:0'],
            'invested_amount' => ['required', 'integer', 'min:0'],
            'current_value' => ['required', 'integer', 'min:0'],
            'acquired_at' => ['nullable', 'date'],
            'active' => ['sometimes', 'boolean'],
            'cdi_linked' => ['sometimes', 'boolean'],
            'cdi_percentage' => ['nullable', 'numeric', 'min:0.0001', 'required_if:cdi_linked,true'],
            'last_yield_date' => ['nullable', 'date'],
        ];
    }
}
