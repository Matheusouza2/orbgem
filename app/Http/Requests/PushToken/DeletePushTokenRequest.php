<?php

namespace App\Http\Requests\PushToken;

use Illuminate\Foundation\Http\FormRequest;

class DeletePushTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['token' => ['required', 'string', 'max:512']];
    }
}
