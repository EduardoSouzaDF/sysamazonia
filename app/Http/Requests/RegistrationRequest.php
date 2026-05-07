<?php
namespace App\Http\Requests;

use App\Models\Category;
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

        $categoryId = $this->input('category_id');
        $category = Category::find($categoryId);

        if($category && !$category->is_honorific){
            //Nao horifico
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
        }else{
                return [
                            'candidate_id' => 'required|exists:candidates,id',
                            'category_id' => 'required|exists:categories,id',
                            'name' => 'required|string|max:255',
                            'state' => 'required|string|max:255',
                            'contact_data' => 'required|string|max:255',


                            // 'presentation' => ['required','string',new WordCountRule(200, 1000)],
                            // 'activities' => ['required','string',new WordCountRule(200, 1000)],677.795.380-9500
                            // 'justification' => ['required','string',new WordCountRule(200, 1000)],

                            'presentation' => ['required','string',new WordCountRule(1, 1000)],
                            'activities' => ['required','string',new WordCountRule(1, 1000)],
                            'justification' => ['required','string',new WordCountRule(1, 1000)],
                        ];
        }



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

            'name.required' => 'O campo nome é obrigatório.',
            'name.string' => 'O nome deve ser um texto válido.',

            'state.required' => 'O campo Estado é obrigatório.',

            'contact_data.required' => 'O campo Dados de Contato é obrigatório.',
            'contact_data.string' => 'O campo Dados de Contato deve ser um texto válido.',


            'presentation.required' => 'O campo Apresentação do(a) Indicado(a) é obrigatório.',
            'presentation.string' => 'O campo Apresentação do(a) Indicado(a) deve ser um texto válido.',
            'presentation.max' => 'O campo Apresentação do(a) Indicado(a) não pode exceder 1000 caracteres.',
            'presentation.min' => 'O campo Apresentação do(a) Indicado(a) deverá ter no mínimo 200 caracteres.',

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
