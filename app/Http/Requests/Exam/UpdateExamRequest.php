<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExamRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'exam_type_id' => ['sometimes', 'required', 'integer', Rule::exists('exam_types', 'id')],
            'session_id' => ['sometimes', 'required', 'integer', Rule::exists('academic_sessions', 'id')],
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'status' => 'nullable|string|in:draft,active,completed,cancelled',
        ];
    }
}
