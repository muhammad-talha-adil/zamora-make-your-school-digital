<?php

namespace App\Http\Requests\Exam;

use App\Enums\Exam\SubjectRole;
use App\Models\Exam\ExamPaper;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Rules for entering one student's marks.
 *
 * There were none worth the name. `'marks' => 'required|array'` was the whole
 * of it, and what was inside was never looked at: text reached a decimal
 * column, negatives were accepted, and **150 out of 100** went in and came back
 * out as a percentage of 150.
 *
 * A mark is bounded by the paper it belongs to, and the paper has to belong to
 * the exam — the id was an array key from the request, so another exam's paper
 * could be posted into this one's result.
 */
class SaveMarksRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The controller authorises against the exam and its lock.
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'exam_id' => ['required', 'integer', Rule::exists('exams', 'id')],
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'enrollment_id' => ['nullable', 'integer', Rule::exists('student_enrollment_records', 'id')],

            'marks' => ['required', 'array', 'min:1'],
            'marks.*.obtained' => ['nullable', 'numeric', 'min:0'],
            'marks.*.obtained_marks' => ['nullable', 'numeric', 'min:0'],
            'marks.*.is_absent' => ['nullable', 'boolean'],
            'marks.*.is_exempt' => ['nullable', 'boolean'],
            'marks.*.remarks' => ['nullable', 'string', 'max:500'],

            // What the subject is to this child. The paper says what it
            // normally is; a school says otherwise for the child taking it as
            // an extra.
            'marks.*.subject_role' => ['nullable', Rule::enum(SubjectRole::class)],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $papers = ExamPaper::where('exam_id', $this->input('exam_id'))
                ->get()
                ->keyBy('id');

            foreach ((array) $this->input('marks', []) as $paperId => $mark) {
                if (! $papers->has((int) $paperId)) {
                    $validator->errors()->add(
                        "marks.{$paperId}",
                        'That paper does not belong to this exam.'
                    );

                    continue;
                }

                $paper = $papers->get((int) $paperId);
                $obtained = $mark['obtained_marks'] ?? $mark['obtained'] ?? null;

                if ($obtained === null || $obtained === '') {
                    continue;
                }

                // A mark cannot exceed the paper it was sat under. Grace marks,
                // which do go above the answer sheet, are a separate thing and
                // are recorded as grace — not by inflating this figure.
                if ((float) $obtained > (float) $paper->total_marks) {
                    $validator->errors()->add(
                        "marks.{$paperId}",
                        sprintf(
                            'Marks cannot be more than the paper total of %s.',
                            rtrim(rtrim(number_format((float) $paper->total_marks, 2), '0'), '.')
                        )
                    );
                }

                if (($mark['is_absent'] ?? false) && (float) $obtained > 0) {
                    $validator->errors()->add(
                        "marks.{$paperId}",
                        'A student marked absent cannot also have marks.'
                    );
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'marks.required' => 'Enter marks for at least one paper.',
            'marks.*.obtained.numeric' => 'Marks must be a number.',
            'marks.*.obtained.min' => 'Marks cannot be negative.',
        ];
    }
}
