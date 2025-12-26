<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'state_name' => 'required|string|max:255|unique:states,state_name,' . $this->route('id'),
        ];
    }

    public function messages(): array
    {
        return [
            'state_name.required' => 'El nombre del estado es obligatorio.',
            'state_name.string' => 'El nombre del estado debe ser texto.',
            'state_name.max' => 'El nombre del estado no puede superar los 255 caracteres.',
            'state_name.unique' => 'Este nombre de estado ya existe.',
        ];
    }


}



