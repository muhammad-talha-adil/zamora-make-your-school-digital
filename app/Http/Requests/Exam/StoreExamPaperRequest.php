<?php

namespace App\Http\Requests\Exam;

use App\Enums\Exam\SubjectRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExamPaperRequest extends FormRequest
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
            'subject_id' => 'required|exists:subjects,id',
            'paper_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'total_marks' => 'required|numeric|min:1',
            'passing_marks' => 'required|numeric|min:0|lte:total_marks',
            // What the subject is to the children who sit it: compulsory,
            // a chosen alternative, or an extra that does not count.
            'subject_role' => ['nullable', Rule::enum(SubjectRole::class)],
        ];
    }
}
