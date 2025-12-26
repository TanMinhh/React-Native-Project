<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'current_password' => 'required|string|min:12',
            'new_password' => 'required|string|min:12|different:current_password',
        ];
    }
}
