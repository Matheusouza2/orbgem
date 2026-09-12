<?php

namespace App\Http\Requests\Planning;

use App\Enums\FinancialCommitmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateFinancialCommitmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['wallet_id' => ['required', 'integer', 'exists:wallets,id'], 'description' => ['required', 'string', 'max:255'], 'type' => ['required', Rule::enum(FinancialCommitmentType::class)], 'original_amount' => ['required', 'integer', 'min:1'], 'installment_amount' => ['required', 'integer', 'min:1'], 'installment_count' => ['required', 'integer', 'min:1'], 'current_installment' => ['sometimes', 'integer', 'min:0'], 'start_date' => ['required', 'date'], 'end_date' => ['required', 'date', 'after_or_equal:start_date'], 'creditor' => ['nullable', 'string', 'max:255'], 'active' => ['sometimes', 'boolean']];
    }
}
