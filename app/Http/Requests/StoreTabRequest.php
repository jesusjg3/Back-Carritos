<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rol_id' => 'required|exists:rols,id',
            'tab_name' => 'required|string|max:255',
            'tab_icon' => 'nullable|string|max:255',
            'tab_order' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'rol_id.required' => 'El rol es obligatorio.',
            'rol_id.exists' => 'El rol seleccionado no es válido.',
            'tab_name.required' => 'El nombre de la pestaña es obligatorio.',
            'tab_name.string' => 'El nombre de la pestaña debe ser texto.',
            'tab_name.max' => 'El nombre de la pestaña no puede superar los 255 caracteres.',
            'tab_icon.string' => 'El icono debe ser texto.',
            'tab_icon.max' => 'El icono no puede superar los 255 caracteres.',
            'tab_order.required' => 'El orden es obligatorio.',
            'tab_order.integer' => 'El orden debe ser un número entero.',
        ];
    }

}



