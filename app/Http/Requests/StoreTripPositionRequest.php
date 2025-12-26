<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTripPositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'type' => 'required|string|max:50', // e.g., 'start', 'update', 'end'
        ];
    }

    public function messages(): array
    {
        return [
            'lat.required' => 'La latitud es obligatoria.',
            'lat.numeric' => 'La latitud debe ser un número.',
            'lat.between' => 'La latitud debe estar entre -90 y 90.',
            'lng.required' => 'La longitud es obligatoria.',
            'lng.numeric' => 'La longitud debe ser un número.',
            'lng.between' => 'La longitud debe estar entre -180 y 180.',
            'type.required' => 'El tipo es obligatorio.',
            'type.string' => 'El tipo debe ser texto.',
            'type.max' => 'El tipo no puede superar los 50 caracteres.',
        ];
    }

}



