<?php

namespace App\Http\Requests\Investment;

use App\Enums\InvestmentPositionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'entry_type' => ['sometimes', Rule::enum(InvestmentPositionType::class)],
            'quantity' => ['nullable', 'numeric', 'min:0.00000001'],
            'unit_price' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
