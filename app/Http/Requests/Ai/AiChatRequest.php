<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class AiChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['wallet_id' => ['required', 'integer', 'exists:wallets,id'], 'message' => ['required', 'string', 'max:1000'], 'history' => ['sometimes', 'array', 'max:10'], 'history.*.role' => ['required', 'in:user,assistant'], 'history.*.content' => ['required', 'string', 'max:2000']];
    }
}
