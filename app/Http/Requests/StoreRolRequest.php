<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rol_name' => 'required|string|max:255|unique:rols,rol_name',
        ];
    }

    public function messages(): array
    {
        return [
            'rol_name.required' => 'El nombre del rol es obligatorio.',
            'rol_name.string' => 'El nombre del rol debe ser texto.',
            'rol_name.max' => 'El nombre del rol no puede superar los 255 caracteres.',
            'rol_name.unique' => 'Este nombre de rol ya existe.',
        ];
    }


}



