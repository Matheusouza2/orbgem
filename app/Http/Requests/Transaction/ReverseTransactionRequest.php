<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class ReverseTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'transaction_date' => ['nullable', 'date'],
            'competence_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
