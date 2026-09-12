<?php

namespace App\Http\Requests\Planning;

use Illuminate\Foundation\Http\FormRequest;

class CreateBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['wallet_id' => ['required', 'integer', 'exists:wallets,id'], 'category_id' => ['required', 'integer', 'exists:categories,id'], 'reference_month' => ['required', 'date_format:Y-m'], 'amount' => ['required', 'integer', 'min:0'], 'active' => ['sometimes', 'boolean']];
    }
}
