<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class SaveMarksRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'students' => 'required|array',
            'students.*.student_id' => 'required|exists:students,id',
            'students.*.obtained_marks' => 'nullable|numeric|min:0',
            'students.*.is_absent' => 'nullable|boolean',
            'students.*.is_exempt' => 'nullable|boolean',
            'students.*.remarks' => 'nullable|string|max:500',
        ];
    }
}
