<?php

namespace App\Http\Requests\BankReconciliation;

use Illuminate\Foundation\Http\FormRequest;

class ImportBankReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'entry_ids' => ['required', 'array', 'min:1', 'max:500'],
            'entry_ids.*' => ['required', 'integer', 'distinct'],
            'acknowledge_warnings' => ['sometimes', 'boolean'],
        ];
    }
}
