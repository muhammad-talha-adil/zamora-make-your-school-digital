<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Rules for correcting a register that has already been taken.
 *
 * The row ids are scoped to the register named in the URL. Accepting bare ids
 * let a teacher post their own class in the URL and another class's row ids in
 * the body, and rewrite marks they were never authorised to touch.
 */
class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The controller authorises `update` on the register, which is also
        // what enforces the lock.
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $registerId = $this->route('attendance')?->id;

        return [
            'attendances' => ['required', 'array', 'min:1'],

            'attendances.*.id' => [
                'required',
                'integer',
                Rule::exists('attendance_students', 'id')->where('attendance_id', $registerId),
            ],
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('attendances', []) as $index => $row) {
                $in = $row['check_in'] ?? null;
                $out = $row['check_out'] ?? null;

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
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attendances.*.id.exists' => 'That mark does not belong to this register.',
        ];
    }
}
