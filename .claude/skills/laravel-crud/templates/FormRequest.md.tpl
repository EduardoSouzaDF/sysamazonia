<?php // Template de Form Request (criar) — copie para app/Http/Requests/Store{Model}Request.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Store{{Model}}Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:{{table}}',
            // ... adicione as regras do recurso
        ];
    }

    /**
     * Get custom validation messages (pt-BR).
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'O email já está em uso.',
            // ... mensagens customizadas
        ];
    }
}
