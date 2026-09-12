<?php

namespace App\Http\Requests\Wallet;

use App\Enums\WalletMemberRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeWalletMemberRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::enum(WalletMemberRole::class)],
        ];
    }
}
