<?php

namespace App\Http\Requests\Transaction;

use App\Enums\PaymentChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAccountTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'integer', 'exists:wallets,id'],
            'from_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'to_account_id' => ['required', 'integer', 'different:from_account_id', 'exists:accounts,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'transaction_date' => ['required', 'date'],
            'competence_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'payment_channel' => ['nullable', Rule::enum(PaymentChannel::class)],
        ];
    }
}
