<?php

namespace App\Http\Requests;

use App\Rules\WordCountRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Crypt;

class CandidateRequest extends FormRequest
{
    private $decodedData = [];

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
            $this->decodedData = $decoded;
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
        $rules = [
            'nome' => 'required|string|max:255|min:5',
            'cpf' => 'required|string|unique:candidates',
            'dt_nascimento' => 'required|date',
            'rg' => 'nullable|string|max:20',
            'rg_expeditor' => 'nullable|string|max:20',
            'rg_uf' => 'nullable|string|max:2',
            'sexo' => 'required',
            'cep' => 'required|string|max:9',
            'ufendereco' => 'required|string|max:2',
            'cidade' => 'required|string|max:100',
            'endereco' => 'required|string|max:255',
            'numero' => 'required|string|max:10',
            'complemento' => 'nullable|string|max:100',
            'ddd' => 'required|string|max:3',
            'celular' => 'required|string|max:15',
            'whatsapp' => 'sometimes|boolean',
            'email' => 'required|email|unique:candidates',
            'instituicao' => 'nullable|string|max:255',
            'escolaridade' => 'required|string',
            'instagram' => 'nullable|string|max:100',
            'facebook' => 'nullable|string|max:100',
            'outra_rede_social' => 'nullable|string|max:100',
            'resumo_curricular' => ['nullable', 'required', 'string', new WordCountRule(100, 1000)],
            'nullable|string|max:1000',
        ];

        if (! empty($this->decodedData['candidate_id'])) {
            $candidateId = Crypt::decryptString($this->decodedData['candidate_id']);
            $rules['email'] = 'required|email|unique:candidates,email,'.$candidateId;
            $rules['cpf'] = 'required|string|unique:candidates,cpf,'.$candidateId;
        }

        return $rules;
    }

    /**
     * Custom message for validation errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'nome.required' => 'O campo nome é obrigatório.',
            'nome.max' => 'O campo nome deve ter no máximo 255 caracteres.',

            'cpf.required' => 'O CPF é obrigatório.',
            'cpf.unique' => 'Este CPF já está cadastrado.',

            'dt_nascimento.required' => 'A data de nascimento é obrigatória.',
            'dt_nascimento.date' => 'A data de nascimento deve ser válida.',

            'sexo.required' => 'O sexo é obrigatório.',
            'sexo.in' => 'O valor selecionado para sexo é inválido.',

            'cep.required' => 'O CEP é obrigatório.',

            'ufendereco.required' => 'O estado (UF) é obrigatório.',

            'cidade.required' => 'A cidade é obrigatória.',

            'endereco.required' => 'O endereço é obrigatório.',

            'numero.required' => 'O número do endereço é obrigatório.',

            'ddd.required' => 'O DDD é obrigatório.',

            'celular.required' => 'O celular é obrigatório.',

            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Insira um e-mail válido.',
            'email.unique' => 'Este e-mail já está em uso.',

            'escolaridade.required' => 'A escolaridade é obrigatória.',
            'escolaridade.in' => 'Escolha uma opção válida de escolaridade.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'sucesso' => false,
            'status' => 'error',
            'erros' => $validator->errors(),
            'codigo' => 'VAL001',
        ], 422)->setEncodingOptions(JSON_UNESCAPED_UNICODE));
    }
}
