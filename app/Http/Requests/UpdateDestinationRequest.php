<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDestinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|min:3|max:255|unique:destinations,name,' . $this->route('id'),
            'description' => 'nullable|string|max:500',
            'latitude' => 'sometimes|required|numeric|between:-90,90',
            'longitude' => 'sometimes|required|numeric|between:-180,180',
            'address' => 'sometimes|required|string|min:5|max:255',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del destino es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'name.unique' => 'Este destino ya existe.',
            'latitude.required' => 'La latitud es obligatoria.',
            'latitude.numeric' => 'La latitud debe ser un número válido.',
            'latitude.between' => 'La latitud debe estar entre -90 y 90.',
            'longitude.required' => 'La longitud es obligatoria.',
            'longitude.numeric' => 'La longitud debe ser un número válido.',
            'longitude.between' => 'La longitud debe estar entre -180 y 180.',
            'address.required' => 'La dirección es obligatoria.',
            'address.min' => 'La dirección debe tener al menos 5 caracteres.',
        ];
    }
}
