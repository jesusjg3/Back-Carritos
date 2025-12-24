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
}
