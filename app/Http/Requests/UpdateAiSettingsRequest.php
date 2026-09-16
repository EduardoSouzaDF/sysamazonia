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
            'provider' => ['required', Rule::in(array_keys(config('ai_evaluation.providers')))],
            'model' => ['required', 'string', 'max:120', 'regex:~^[A-Za-z0-9][A-Za-z0-9._:/-]*$~'],
            'api_key' => ['nullable', 'string', 'min:1', 'max:1000'],
            'base_url' => ['nullable', 'required_if:provider,local,openai-compatible', 'string', 'max:500', 'url:http,https', function ($attribute, $value, $fail) {
                $parts = parse_url($value);
                if ($parts === false || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
                    $fail('Base URL não pode conter credenciais, query ou fragmento.');
                }
                if ($this->input('provider') === 'openai-compatible' && ($parts['scheme'] ?? '') !== 'https') {
                    $fail('Provider custom remoto exige HTTPS.');
                }
            }],
            'local_type' => ['nullable', Rule::in(['ollama', 'openai-compatible'])],
            'technical_prompt' => ['nullable', 'string', 'max:100000'],
            'selection_prompt' => ['nullable', 'string', 'max:100000'],
            'evaluation_enabled' => ['nullable', 'boolean'],
            'selection_enabled' => ['nullable', 'boolean'],
            'technical_evaluator_id' => ['required', 'integer', 'exists:users,id'],
            'selection_evaluator_id' => ['required', 'integer', 'exists:users,id'],
            'connect_timeout' => ['required', 'integer', 'min:1', 'max:30'],
            'timeout' => ['required', 'integer', 'min:5', 'max:60'],
            'tries' => ['required', 'integer', 'min:1', 'max:10'],
            'knowledge_version' => ['nullable', 'string', 'max:80'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['base_url' => in_array($this->input('provider'), ['local', 'openai-compatible'], true) ? $this->input('base_url') : null, 'local_type' => $this->input('local_type') ?: 'ollama', 'evaluation_enabled' => $this->boolean('evaluation_enabled'), 'selection_enabled' => $this->boolean('selection_enabled')]);
    }
}
