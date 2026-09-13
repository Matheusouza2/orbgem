<?php

namespace App\Http\Requests\CreditCard;

use Illuminate\Foundation\Http\FormRequest;

class CreateCreditCardPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['wallet_id' => ['required', 'integer', 'exists:wallets,id'], 'credit_card_id' => ['required', 'integer', 'exists:credit_cards,id'], 'category_id' => ['nullable', 'integer', 'exists:categories,id'], 'merchant_id' => ['nullable', 'integer', 'exists:merchants,id'], 'description' => ['required', 'string', 'max:255'], 'purchase_date' => ['required', 'date'], 'total_amount' => ['required', 'integer', 'min:1'], 'installment_count' => ['required', 'integer', 'between:1,120'], 'is_third_party' => ['sometimes', 'boolean']];
    }
}
