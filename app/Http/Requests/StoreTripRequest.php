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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $lat = $this->input('origin_lat');
            $lng = $this->input('origin_lng');

            if ($lat && $lng) {
                // Punto central aproximado del campus
                $centerLat = -0.9525;
                $centerLng = -80.7450;
                
                // Calcular distancia en km (Fórmula de Haversine)
                $earthRadius = 6371;
                $dLat = deg2rad($lat - $centerLat);
                $dLng = deg2rad($lng - $centerLng);
                $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($centerLat)) * cos(deg2rad($lat)) * sin($dLng/2) * sin($dLng/2);
                $c = 2 * atan2(sqrt($a), sqrt(1-$a));
                $distance = $earthRadius * $c;

                // Límite de distancia de 1.5 km
                if ($distance > 1.5) {
                    $validator->errors()->add('origin_lat', 'Estás fuera de la zona de servicio permitida para pedir carritos.');
                }
            }
        });
    }
}



