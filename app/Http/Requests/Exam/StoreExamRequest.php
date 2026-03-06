<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExamRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'exam_type_id' => ['required', 'integer', Rule::exists('exam_types', 'id')],
            'session_id' => ['required', 'integer', Rule::exists('academic_sessions', 'id')],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'nullable|string|in:draft,active,completed,cancelled',
        ];
    }

    public function messages()
    {
        return [
            'session_id.required' => 'Please select a session.',
            'session_id.exists' => 'The selected session is invalid. Please select a valid session from the list.',
            'exam_type_id.required' => 'Please select an exam type.',
            'exam_type_id.exists' => 'The selected exam type is invalid.',
        ];
    }
}
