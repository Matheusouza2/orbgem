<?php

namespace App\Http\Requests\CreditCard;

use Illuminate\Foundation\Http\FormRequest;

class CreateCreditCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['wallet_id' => ['required', 'integer', 'exists:wallets,id'], 'owner_wallet_member_id' => ['nullable', 'integer', 'exists:wallet_members,id'], 'account_id' => ['nullable', 'integer', 'exists:accounts,id'], 'name' => ['required', 'string', 'max:255'], 'institution' => ['nullable', 'string', 'max:255'], 'limit' => ['required', 'integer', 'min:0'], 'closing_day' => ['required', 'integer', 'between:1,31'], 'due_day' => ['required', 'integer', 'between:1,31'], 'active' => ['sometimes', 'boolean']];
    }
}
