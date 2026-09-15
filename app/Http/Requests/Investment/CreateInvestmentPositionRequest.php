<?php

namespace App\Http\Requests\Investment;

use Illuminate\Foundation\Http\FormRequest;

class CreateInvestmentPositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'value' => ['required', 'integer', 'min:1'],
            'position_date' => ['required', 'date'],
            'quantity' => ['nullable', 'numeric', 'min:0.00000001'],
            'unit_price' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
