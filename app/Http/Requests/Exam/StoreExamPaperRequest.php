<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamPaperRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'exam_group_id' => 'required|exists:exam_groups,id',
            'subject_id' => 'required|exists:subjects,id',
            'exam_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'total_marks' => 'required|numeric|min:1',
            'passing_marks' => 'required|numeric|min:0|lte:total_marks',
        ];
    }
}
