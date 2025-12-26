<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class TaskRequest extends FormRequest
{
    public function rules()
    {
        if ($this->isMethod('post')) {
            return [
                'title' => 'required|string|max:255',
                'type' => 'nullable|in:CALL,MEET,NOTE,OTHER',
                'lead_id' => 'nullable|exists:leads,id',
                'opportunity_id' => 'nullable|exists:opportunities,id',
                'due_date' => 'required|date',
                'status' => 'in:IN_PROGRESS,DONE,OVERDUE',
                'assigned_to' => 'nullable|exists:users,id'
            ];
        }

        return [
            'title' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|nullable|in:CALL,MEET,NOTE,OTHER',
            'lead_id' => 'sometimes|nullable|exists:leads,id',
            'opportunity_id' => 'sometimes|nullable|exists:opportunities,id',
            'due_date' => 'sometimes|required|date',
            'status' => 'sometimes|in:IN_PROGRESS,DONE,OVERDUE',
            'assigned_to' => 'sometimes|nullable|exists:users,id'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'errors' => $validator->errors()
        ], 422));
    }
}
