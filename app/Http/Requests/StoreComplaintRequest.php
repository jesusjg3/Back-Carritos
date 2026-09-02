<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'trip_id' => 'nullable|exists:trips,id',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
        ];
    }
}
