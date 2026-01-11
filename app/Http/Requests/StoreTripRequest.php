<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'origin_lat' => 'required|numeric|between:-90,90',
            'origin_lng' => 'required|numeric|between:-180,180',
            'origin_address' => 'required|string|max:255',
            'destination_lat' => 'required|numeric|between:-90,90',
            'destination_lng' => 'required|numeric|between:-180,180',
            'destination_address' => 'required|string|max:255',
            'distance' => 'required|numeric|min:0',
            'passengers_count' => 'required|integer|min:1|max:5',
        ];
    }

    public function messages(): array
    {
        return [
            'origin_lat.required' => 'La latitud de origen es obligatoria.',
            'origin_lat.numeric' => 'La latitud de origen debe ser un número.',
            'origin_lat.between' => 'La latitud de origen debe estar entre -90 y 90.',
            'origin_lng.required' => 'La longitud de origen es obligatoria.',
            'origin_lng.numeric' => 'La longitud de origen debe ser un número.',
            'origin_lng.between' => 'La longitud de origen debe estar entre -180 y 180.',
            'destination_lat.required' => 'La latitud de destino es obligatoria.',
            'destination_lat.numeric' => 'La latitud de destino debe ser un número.',
            'destination_lat.between' => 'La latitud de destino debe estar entre -90 y 90.',
            'destination_lng.required' => 'La longitud de destino es obligatoria.',
            'destination_lng.numeric' => 'La longitud de destino debe ser un número.',
            'destination_lng.between' => 'La longitud de destino debe estar entre -180 y 180.',
            'distance.required' => 'La distancia es obligatoria.',
            'distance.numeric' => 'La distancia debe ser un número.',
            'distance.min' => 'La distancia debe ser mayor o igual a 0.',
            'passengers_count.required' => 'El número de pasajeros es obligatorio.',
            'passengers_count.integer' => 'El número de pasajeros debe ser un número entero.',
            'passengers_count.min' => 'Debe haber al menos 1 pasajero.',
            'passengers_count.max' => 'El máximo de pasajeros es 5.',
        ];
    }

}



