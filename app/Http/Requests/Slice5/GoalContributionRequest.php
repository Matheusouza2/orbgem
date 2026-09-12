<?php

namespace App\Http\Requests\Slice5;

use Illuminate\Foundation\Http\FormRequest;

class GoalContributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['amount' => ['required', 'integer', 'min:1'], 'contributed_at' => ['nullable', 'date'], 'note' => ['nullable', 'string', 'max:255']];
    }
}
