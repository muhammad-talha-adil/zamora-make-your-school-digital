<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'exam_group_id' => 'required|exists:exam_groups,id',
            'student_id' => 'required|exists:students,id',
            'status' => 'nullable|string|in:registered,withdrawn,absent,passed,failed',
        ];
    }
}
