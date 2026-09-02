<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id . ',id,deleted_at,NULL',
            'password' => 'sometimes|nullable|string|min:8',
            'is_active' => 'sometimes|boolean',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ];
    }
}
