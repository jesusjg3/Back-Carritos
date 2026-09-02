<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTripRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'score' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'receiver_id' => 'nullable|integer|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'score.required' => 'La puntuación es obligatoria.',
            'score.integer' => 'La puntuación debe ser un número entero.',
            'score.min' => 'La puntuación mínima es 1.',
            'score.max' => 'La puntuación máxima es 5.',
            'comment.string' => 'El comentario debe ser texto.',
            'comment.max' => 'El comentario no puede superar los 1000 caracteres.',
        ];
    }
}



