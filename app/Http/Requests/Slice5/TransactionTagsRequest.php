<?php

namespace App\Http\Requests\Slice5;

use Illuminate\Foundation\Http\FormRequest;

class TransactionTagsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['tag_ids' => ['required', 'array'], 'tag_ids.*' => ['integer', 'distinct', 'exists:tags,id']];
    }
}
