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
        $rules = [
            'full_name'     => 'sometimes|string|max:255',
            'email'         => 'nullable|email',
            'phone_number'  => 'nullable|string|max:50',
            'company'       => 'nullable|string|max:255',
            'source'        => 'nullable|string|max:255',
            'status'        => 'nullable|in:LEAD,CONTACTED,CARING,NO_NEED,PURCHASED',
            'owner_id'      => 'sometimes|exists:users,id',
            'assigned_to'   => 'sometimes|nullable|exists:users,id',
        ];

        // full_name is required only when creating a new lead
        if ($this->isMethod('POST')) {
            $rules['full_name'] = 'required|string|max:255';
        }

        return $rules;
    }
}
