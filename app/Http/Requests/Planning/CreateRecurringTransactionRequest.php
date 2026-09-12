<?php

namespace App\Http\Requests\Planning;

use App\Enums\RecurringFrequency;
use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRecurringTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['wallet_id' => ['required', 'integer', 'exists:wallets,id'], 'account_id' => ['nullable', 'integer', 'exists:accounts,id'], 'category_id' => ['nullable', 'integer', 'exists:categories,id'], 'description' => ['required', 'string', 'max:255'], 'type' => ['required', Rule::enum(TransactionType::class), Rule::notIn([TransactionType::TRANSFER->value])], 'amount' => ['required', 'integer', 'min:1'], 'frequency' => ['required', Rule::enum(RecurringFrequency::class)], 'start_date' => ['required', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date'], 'due_day' => ['nullable', 'integer', 'between:1,31'], 'auto_create' => ['sometimes', 'boolean'], 'active' => ['sometimes', 'boolean']];
    }
}
