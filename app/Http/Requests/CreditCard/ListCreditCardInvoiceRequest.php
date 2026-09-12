<?php

namespace App\Http\Requests\CreditCard;

use Illuminate\Foundation\Http\FormRequest;

class ListCreditCardInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['wallet_id' => ['required', 'integer', 'exists:wallets,id'], 'credit_card_id' => ['nullable', 'integer'], 'status' => ['nullable', 'string', 'in:OPEN,CLOSED,PAID,OVERDUE']];
    }
}
