<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExamPaperRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'exam_group_id' => 'sometimes|required|exists:exam_groups,id',
            'subject_id' => 'sometimes|required|exists:subjects,id',
            'exam_date' => 'sometimes|required|date',
            'start_time' => 'sometimes|required',
            'end_time' => 'sometimes|required|after:start_time',
            'total_marks' => 'sometimes|required|numeric|min:1',
            'passing_marks' => 'sometimes|required|numeric|min:0|lte:total_marks',
        ];
    }
}
