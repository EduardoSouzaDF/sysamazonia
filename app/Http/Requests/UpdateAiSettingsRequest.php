<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAiSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', Rule::in(['openai', 'gemini'])],
            'model' => ['required', 'string', 'max:120'],
            'api_key' => ['nullable', 'string', 'min:8', 'max:1000'],
            'technical_prompt' => ['nullable', 'string', 'max:100000'],
            'selection_prompt' => ['nullable', 'string', 'max:100000'],
            'evaluation_enabled' => ['nullable', 'boolean'],
            'selection_enabled' => ['nullable', 'boolean'],
            'technical_evaluator_id' => ['required', 'integer', 'exists:users,id'],
            'selection_evaluator_id' => ['required', 'integer', 'exists:users,id'],
            'connect_timeout' => ['required', 'integer', 'min:1', 'max:30'],
            'timeout' => ['required', 'integer', 'min:5', 'max:300'],
            'tries' => ['required', 'integer', 'min:1', 'max:10'],
            'knowledge_version' => ['nullable', 'string', 'max:80'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['evaluation_enabled' => $this->boolean('evaluation_enabled'), 'selection_enabled' => $this->boolean('selection_enabled')]);
    }
}
