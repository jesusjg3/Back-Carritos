<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand' => 'required|string|max:50',
            'model' => 'required|string|max:50',
            'plate' => ['required', 'string', 'max:20', Rule::unique('vehicles', 'plate')->whereNull('deleted_at')],
            'color' => 'required|string|max:30',
            'capacity' => 'required|integer|min:1|max:10',
        ];
    }
}
