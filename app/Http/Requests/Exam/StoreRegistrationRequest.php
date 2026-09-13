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
            'exam_id' => 'required|exists:exams,id',
            'student_id' => 'required|exists:students,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'class_id' => 'nullable|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'enrollment_id' => 'nullable|exists:student_enrollment_records,id',
            'roll_no_snapshot' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:registered,withdrawn,absent,passed,failed',
        ];
    }
}
