<?php

namespace App\Http\Requests\Slice5;

use Illuminate\Foundation\Http\FormRequest;

class Slice5QueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['sometimes', 'integer', 'exists:wallets,id'],
            'month' => ['sometimes', 'date_format:Y-m'],
            'attachable_type' => ['sometimes', 'string', 'in:transaction,financial_goal,financial_commitment,recurring_transaction,budget,import_batch,credit_card'],
            'attachable_id' => ['sometimes', 'integer'],
        ];
    }
}
