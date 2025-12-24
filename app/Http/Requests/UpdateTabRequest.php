<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rol_id' => 'sometimes|exists:rols,id',
            'tab_name' => 'sometimes|string|max:255',
            'tab_icon' => 'nullable|string|max:255',
            'tab_order' => 'sometimes|integer',
        ];
    }
}
