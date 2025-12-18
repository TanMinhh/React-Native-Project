<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name'     => 'required|string|max:255',
            'email'         => 'nullable|email',
            'phone_number'  => 'nullable|string|max:50',
            'company'       => 'nullable|string|max:255',
            'status'        => 'nullable|in:NEW,CONTACTING,AGREEMENT,LOST',
            'owner_id'      => 'sometimes|exists:users,id',
        ];
    }
}
