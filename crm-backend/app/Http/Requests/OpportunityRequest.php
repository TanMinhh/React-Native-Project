<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        if ($this->isMethod('post')) {
            return [
                'lead_id' => 'required|exists:leads,id',
                'stage' => 'required|in:PROSPECTING,PROPOSAL,NEGOTIATION,WON,LOST',
                'estimated_value' => 'numeric',
                'expected_close_date' => 'date',
                'owner_id' => 'sometimes|exists:users,id',
            ];
        }

        return [
            'lead_id' => 'sometimes|exists:leads,id',
            'stage' => 'sometimes|in:PROSPECTING,PROPOSAL,NEGOTIATION,WON,LOST',
            'estimated_value' => 'sometimes|numeric',
            'expected_close_date' => 'sometimes|date',
            'owner_id' => 'sometimes|exists:users,id',
        ];
    }
}
