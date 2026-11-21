<?php

namespace App\Http\Requests\Timetable;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveTimetablePeriodRequest extends FormRequest
{
    /**
     * Authorization is done in the controller through `TimetablePeriodPolicy`.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
            'session_id' => ['required', 'integer', 'exists:academic_sessions,id'],
            'name' => ['required', 'string', 'max:60'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100'],
            'is_break' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'end_time.after' => 'A period must end after it starts.',
        ];
    }
}
