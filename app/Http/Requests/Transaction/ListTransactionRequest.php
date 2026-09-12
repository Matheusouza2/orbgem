<?php

namespace App\Http\Requests\Transaction;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'integer', 'exists:wallets,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', Rule::in(['id', 'amount', 'transaction_date', 'competence_date', 'created_at'])],
            'sort_direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'account_id' => ['nullable', 'integer'],
            'merchant_id' => ['nullable', 'integer'],
            'type' => ['nullable', Rule::enum(TransactionType::class)],
            'status' => ['nullable', Rule::enum(TransactionStatus::class)],
            'month' => ['nullable', 'date_format:Y-m'],
            'transaction_date_from' => ['nullable', 'date'],
            'transaction_date_to' => ['nullable', 'date', 'after_or_equal:transaction_date_from'],
            'competence_date_from' => ['nullable', 'date'],
            'competence_date_to' => ['nullable', 'date', 'after_or_equal:competence_date_from'],
        ];
    }
}
