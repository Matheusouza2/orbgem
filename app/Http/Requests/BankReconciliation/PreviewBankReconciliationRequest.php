<?php

namespace App\Http\Requests\BankReconciliation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class PreviewBankReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'month' => ['required', 'date_format:Y-m'],
            'file' => ['required', 'file', 'max:20480'],
        ];
    }

    protected function passedValidation(): void
    {
        $extension = strtolower($this->file('file')?->getClientOriginalExtension() ?? '');
        if (! in_array($extension, ['pdf', 'ofx'], true)) {
            throw ValidationException::withMessages(['file' => 'Envie um arquivo PDF ou OFX de até 20 MB.']);
        }
    }
}
