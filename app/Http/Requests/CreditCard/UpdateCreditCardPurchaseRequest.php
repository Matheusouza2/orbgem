<?php

namespace App\Http\Requests\CreditCard;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCreditCardPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['description' => ['required', 'string', 'max:255'], 'purchase_date' => ['required', 'date'], 'total_amount' => ['required', 'integer', 'min:1'], 'category_id' => ['nullable', 'integer', 'exists:categories,id'], 'merchant_id' => ['nullable', 'integer', 'exists:merchants,id'], 'is_third_party' => ['sometimes', 'boolean']];
    }
}
