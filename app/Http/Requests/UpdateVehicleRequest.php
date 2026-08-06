<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->route('vehicle');

        return [
            'brand' => 'sometimes|required|string|max:50',
            'model' => 'sometimes|required|string|max:50',
            'plate' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('vehicles', 'plate')->ignore($id)->whereNull('deleted_at')],
            'color' => 'sometimes|required|string|max:30',
            'capacity' => 'sometimes|required|integer|min:1|max:10',
            'status' => 'sometimes|string|in:active,inactive,maintenance',
        ];
    }
}
