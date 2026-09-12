<?php

namespace App\Http\Requests\Account;

use App\Enums\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'integer', 'exists:wallets,id'],
            'owner_wallet_member_id' => ['nullable', 'integer', 'exists:wallet_members,id'],
            'name' => ['required', 'string', 'max:255'],
            'institution' => ['nullable', 'string', 'max:255'],
            'bank_code' => ['nullable', 'string', 'size:8', 'regex:/^\d{8}$/'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'type' => ['required', Rule::enum(AccountType::class)],
            'initial_balance' => ['required', 'integer'],
            'is_default' => ['required', 'boolean'],
            'show_in_dashboard' => ['required', 'boolean'],
            'ignore_in_totals' => ['required', 'boolean'],
            'active' => ['required', 'boolean'],
        ];
    }
}
