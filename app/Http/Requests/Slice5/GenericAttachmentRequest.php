<?php

namespace App\Http\Requests\Slice5;

use Illuminate\Foundation\Http\FormRequest;

class GenericAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'integer', 'exists:wallets,id'],
            'attachable_type' => ['required', 'string', 'in:transaction,financial_goal,financial_commitment,recurring_transaction,budget,import_batch,credit_card'],
            'attachable_id' => ['required', 'integer'],
            'file' => ['required', 'file', 'max:10240'],
        ];
    }
}
