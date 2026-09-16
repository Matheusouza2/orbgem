<?php

namespace App\Http\Requests\CreditCard;

use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListCreditCardTransactionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'integer', 'exists:wallets,id'],
            'month' => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', 'string', 'in:POSTED,PROJECTED,CANCELLED'],
            'type' => ['nullable', Rule::enum(TransactionType::class)],
            'category_id' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'include_third_party' => ['sometimes', 'boolean'],
        ];
    }
}
