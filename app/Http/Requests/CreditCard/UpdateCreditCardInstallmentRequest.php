<?php

namespace App\Http\Requests\CreditCard;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCreditCardInstallmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['description' => ['required', 'string', 'max:255'], 'amount' => ['required', 'integer', 'min:1'], 'transaction_date' => ['required', 'date'], 'category_id' => ['nullable', 'integer', 'exists:categories,id'], 'merchant_id' => ['nullable', 'integer', 'exists:merchants,id'], 'is_third_party' => ['sometimes', 'boolean']];
    }
}
