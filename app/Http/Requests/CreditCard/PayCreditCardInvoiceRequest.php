<?php

namespace App\Http\Requests\CreditCard;

use Illuminate\Foundation\Http\FormRequest;

class PayCreditCardInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['account_id' => ['required', 'integer', 'exists:accounts,id'], 'amount' => ['required', 'integer', 'min:1'], 'payment_date' => ['required', 'date']];
    }
}
