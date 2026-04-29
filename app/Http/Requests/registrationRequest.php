<?php
namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Rules\WordCountRule;
use Illuminate\Support\Facades\Crypt;

class RegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Altere conforme a lógica de permissão
    }

    protected function prepareForValidation()
    {
        $data = $this->input('data');

        if (is_string($data)) {
            $decoded = json_decode($data, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                // Isso joga os campos do JSON para o nível principal do Request
                $this->merge($decoded);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'candidate_id' => 'required|exists:candidates,id',
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'coautores' => 'nullable|string|max:1000',
            // 'resumo' => ['required','string',new WordCountRule(500, 1000)],
            // 'desenvolvimento' => ['required','string',new WordCountRule(2000, 3000)],
            // 'objetivo' => ['required','string',new WordCountRule(1000, 2000)],
            // 'conclusao' => ['required','string',new WordCountRule(500, 1000)],

            'resumo' => ['required','string',new WordCountRule(1, 1000)],
            'desenvolvimento' => ['required','string',new WordCountRule(1, 1000)],
            'objetivo' => ['required','string',new WordCountRule(1, 1000)],
            'conclusao' => ['required','string',new WordCountRule(1,1000)],

            'status' => 'nullable|integer',
        ];

    }

    /**
     * Custom message for validation errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'candidate_id.required' => 'O campo candidato é obrigatório.',
            'candidate_id.exists' => 'O candidato selecionado não existe.',
            'category_id.required' => 'O campo categoria é obrigatório.',
            'category_id.exists' => 'A categoria selecionada não existe.',
            'title.required' => 'O campo título é obrigatório.',
            'title.string' => 'O título deve ser um texto válido.',
            'coautores.string' => 'Os coautores devem ser um texto válido.',
            'resumo.required' => 'O campo resumo é obrigatório.',
            'resumo.string' => 'O resumo deve ser um texto válido.',
            'resumo.string' => 'O resumo deve ser um texto válido.',
            'desenvolvimento.required' => 'O campo desenvolvimento é obrigatório.',
            'desenvolvimento.string' => 'O desenvolvimento deve ser um texto válido.',
            'desenvolvimento.max' => 'O desenvolvimento não pode exceder 5000 caracteres.',
            'objetivo.required' => 'O campo objetivo é obrigatório.',
            'objetivo.string' => 'O objetivo deve ser um texto válido.',
            'objetivo.max' => 'O objetivo não pode exceder 2000 caracteres.',
            'conclusao.required' => 'O campo conclusão é obrigatório.',
            'conclusao.string' => 'A conclusão deve ser um texto válido.',
            'status.integer' => 'O status deve ser um número inteiro.',
            'status.in' => 'O status deve ser 0 (rascunho), 1 (pendente), 2 (aprovado) ou 3 (rejeitado).',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'sucesso' => false,
            'status' => 'error',
            'erros'   => $validator->errors(),
            'codigo'  => 'VAL001'
        ], 422)->setEncodingOptions(JSON_UNESCAPED_UNICODE));
    }
}
