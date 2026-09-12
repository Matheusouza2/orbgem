<?php

namespace App\Http\Requests\Slice5;

use Illuminate\Foundation\Http\FormRequest;

class FinancialGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['wallet_id' => ['required', 'integer', 'exists:wallets,id'], 'name' => ['required', 'string', 'max:255'], 'target_amount' => ['required', 'integer', 'min:1'], 'current_amount' => ['sometimes', 'integer', 'min:0'], 'deadline' => ['nullable', 'date'], 'status' => ['sometimes', 'string', 'in:ACTIVE,COMPLETED,CANCELLED'], 'active' => ['sometimes', 'boolean']];
    }
}
