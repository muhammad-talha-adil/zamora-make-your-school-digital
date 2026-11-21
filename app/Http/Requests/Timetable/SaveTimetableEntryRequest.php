<?php

namespace App\Http\Requests\Timetable;

use App\Models\Timetable\TimetableEntry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTimetableEntryRequest extends FormRequest
{
    /**
     * Authorization is done in the controller through `TimetableEntryPolicy`.
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
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'staff_profile_id' => ['required', 'integer', 'exists:staff_profiles,id'],
            'period_id' => ['required', 'integer', 'exists:timetable_periods,id'],
            'weekday' => ['required', 'integer', Rule::in(array_keys(TimetableEntry::WEEKDAYS))],
            'room' => ['nullable', 'string', 'max:40'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'staff_profile_id.required' => 'Choose a teacher.',
            'period_id.required' => 'Choose a period.',
        ];
    }
}
