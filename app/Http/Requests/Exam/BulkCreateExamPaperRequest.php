<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class BulkCreateExamPaperRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'exam_group_id' => 'required|exists:exam_groups,id',
            'papers' => 'required|array',
            'papers.*.subject_id' => 'required|exists:subjects,id',
            'papers.*.exam_date' => 'required|date',
            'papers.*.start_time' => 'required',
            'papers.*.end_time' => 'required|after:start_time',
            'papers.*.total_marks' => 'required|numeric|min:1',
            'papers.*.passing_marks' => 'required|numeric|min:0|lte:total_marks',
        ];
    }
}
