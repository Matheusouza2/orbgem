<?php

namespace App\Http\Requests\Investment;

use Illuminate\Foundation\Http\FormRequest;

class CreateInvestmentIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'investment_id' => ['required', 'integer', 'exists:investments,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'transaction_date' => ['required', 'date'],
        ];
    }
}
