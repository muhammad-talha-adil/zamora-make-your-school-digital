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
            'exam_id' => 'required|exists:exams,id',
            'scope_type' => 'required|in:SCHOOL,CLASS,SECTION',
            'campus_id' => 'nullable|exists:campuses,id',
            'class_id' => 'nullable|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'papers' => 'required|array',
            'papers.*.subject_id' => 'required|exists:subjects,id',
            'papers.*.paper_date' => 'required|date',
            'papers.*.start_time' => 'required',
            'papers.*.end_time' => 'required|after:start_time',
            'papers.*.total_marks' => 'required|numeric|min:1',
            'papers.*.passing_marks' => 'required|numeric|min:0|lte:papers.*.total_marks',
        ];
    }
}
