<?php

namespace App\Http\Requests\Attendance;

use App\Models\Section;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Rules for taking a class register.
 *
 * These sat inline in the controller, against this project's own convention,
 * and two of them were not there at all: `section_id` was checked for presence
 * and nothing else, so any integer reached the insert and surfaced as
 * "Failed to record attendance: SQLSTATE…"; and the check-in and check-out
 * times were validated on every path except this one, which is the path the
 * marking screen actually posts to.
 */
class StoreBulkAttendanceRequest extends FormRequest
{
    /**
     * The section id the form sends when a class is marked as a whole.
     *
     * Each child is then filed under their own section rather than one shared
     * register. It is a real value the form sends, so it is named here rather
     * than left as a bare zero in the rules.
     */
    public const ALL_SECTIONS = 0;

    public function authorize(): bool
    {
        // Authorisation is the controller's: it has to check `update` against
        // each register that already exists, which is what enforces the lock.
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'attendance_date' => ['required', 'date'],
            'campus_id' => ['required', 'integer', Rule::exists('campuses', 'id')],
            'session_id' => ['required', 'integer', Rule::exists('academic_sessions', 'id')],
            'class_id' => ['required', 'integer', Rule::exists('school_classes', 'id')],

            /*
             * A section that exists and belongs to the class being marked, the
             * zero that means the whole class, or nothing at all — a class need
             * not have sections, and those children were unmarkable while this
             * was `required`.
             */
            'section_id' => ['nullable', 'integer', $this->sectionRule()],

            'attendances' => ['required', 'array', 'min:1'],
            'attendances.*.student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'attendances.*.attendance_status_id' => [
                'required',
                'integer',
                Rule::exists('attendance_statuses', 'id')->where('is_active', true),
            ],
            'attendances.*.leave_type_id' => ['nullable', 'integer', Rule::exists('leave_types', 'id')],
            'attendances.*.check_in' => ['nullable', 'date_format:H:i'],
            'attendances.*.check_out' => ['nullable', 'date_format:H:i'],
            'attendances.*.remarks' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * A section of the class being marked, or the whole class.
     */
    private function sectionRule(): ValidationRule
    {
        return new class($this->input('class_id')) implements ValidationRule
        {
            public function __construct(private mixed $classId) {}

            public function validate(string $attribute, mixed $value, \Closure $fail): void
            {
                if ((int) $value === StoreBulkAttendanceRequest::ALL_SECTIONS) {
                    return;
                }

                $belongs = Section::where('id', $value)
                    ->where('class_id', $this->classId)
                    ->exists();

                if (! $belongs) {
                    $fail('The selected section does not belong to this class.');
                }
            }
        };
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('attendances', []) as $index => $row) {
                $in = $row['check_in'] ?? null;
                $out = $row['check_out'] ?? null;

                // Checked on the individual and edit paths already; this is the
                // one the screen uses, and it accepted a child leaving before
                // they arrived.
                if ($in && $out && $out <= $in) {
                    $validator->errors()->add(
                        "attendances.{$index}.check_out",
                        'Check-out time must be after check-in time.'
                    );
                }

                if ($out && ! $in) {
                    $validator->errors()->add(
                        "attendances.{$index}.check_in",
                        'Give a check-in time as well as a check-out time.'
                    );
                }
            }

            // The same child twice in one submission would have the second
            // silently overwrite the first.
            $studentIds = collect($this->input('attendances', []))->pluck('student_id')->filter();

            if ($studentIds->count() !== $studentIds->unique()->count()) {
                $validator->errors()->add('attendances', 'Each student may only appear once on a register.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attendances.required' => 'Mark at least one student before saving.',
            'attendances.*.attendance_status_id.required' => 'Every student needs a status.',
            'section_id.required' => 'Choose a section, or the whole class.',
        ];
    }
}
