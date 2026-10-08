<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReprocessAiExecutionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'execution_ids' => ['required', 'array', 'min:1', 'max:50'],
            'execution_ids.*' => ['required', 'integer', 'distinct', 'exists:ai_executions,id'],
            'configuration' => ['required', 'in:previous,current'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'confirmed' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'execution_ids.required' => 'Selecione ao menos uma execução para reprocessar.',
            'execution_ids.max' => 'Selecione no máximo 50 execuções por solicitação.',
            'execution_ids.*.distinct' => 'Cada execução deve ser selecionada apenas uma vez.',
            'execution_ids.*.exists' => 'Uma das execuções selecionadas não existe mais.',
            'configuration.required' => 'Escolha a configuração atual ou anterior.',
            'configuration.in' => 'Escolha a configuração atual ou anterior.',
            'reason.required' => 'Informe o motivo do reprocessamento.',
            'reason.min' => 'O motivo deve ter ao menos 10 caracteres.',
            'reason.max' => 'O motivo deve ter no máximo 1000 caracteres.',
            'confirmed.required' => 'Confirme o reprocessamento antes de continuar.',
            'confirmed.accepted' => 'Confirme o reprocessamento antes de continuar.',
        ];
    }
}
