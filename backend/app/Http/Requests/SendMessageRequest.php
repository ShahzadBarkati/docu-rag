<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:1', 'max:10000'],
            'conversation_id' => ['nullable', 'integer', 'exists:conversations,id'],
            'document_id' => ['nullable', 'integer', 'exists:documents,id'],
        ];
    }
}
