<?php

namespace App\Http\Requests\OpenFinance;

use Illuminate\Foundation\Http\FormRequest;

class SyncConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date', 'before_or_equal:to'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }
}
