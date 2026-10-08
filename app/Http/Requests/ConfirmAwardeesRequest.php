<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmAwardeesRequest extends FormRequest
{
    /**
     * O perfil admin é garantido pelo middleware `CheckAdmin` da rota
     * (spec 0004 / RF-01); nenhuma regra adicional de autorização aqui.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras da confirmação de agraciados (spec 0004 / RF-11): de 1 até
     * `recipients_count` ids distintos. Pertencimento à categoria, qualificação
     * e votação finalizada são revalidados no controller, em transação.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        /** @var Category $category */
        $category = $this->route('category');

        return [
            'inscriptions' => ['required', 'array', 'min:1', 'max:'.max(1, (int) $category->recipients_count)],
            'inscriptions.*' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }

    /**
     * Mensagens customizadas em pt-BR.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'inscriptions.required' => 'Selecione ao menos um agraciado.',
            'inscriptions.array' => 'A lista de agraciados é inválida.',
            'inscriptions.min' => 'Selecione ao menos um agraciado.',
            'inscriptions.max' => 'Selecione no máximo :max agraciado(s).',
            'inscriptions.*.required' => 'O identificador de cada inscrição é obrigatório.',
            'inscriptions.*.integer' => 'O identificador de cada inscrição é inválido.',
            'inscriptions.*.min' => 'O identificador de cada inscrição é inválido.',
            'inscriptions.*.distinct' => 'A lista contém inscrição repetida.',
        ];
    }
}
