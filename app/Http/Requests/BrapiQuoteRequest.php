<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BrapiQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['symbol' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9.-]+$/']];
    }
}
