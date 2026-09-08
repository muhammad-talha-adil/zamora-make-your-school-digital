<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Rules for the roster the marking screen asks for.
 *
 * This was the one place in the module that built a `Validator` by hand and
 * returned its own 422, which meant its errors reached the screen in a
 * different shape from every other endpoint's.
 */
class StudentsForAttendanceRequest extends FormRequest
{
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
            'class_id' => ['required', 'integer', Rule::exists('school_classes', 'id')],

            // Blank, `all`, or a real section: the screen sends all three,
            // depending on whether one section or the whole class is being
            // marked.
            'section_id' => ['nullable', Rule::exists('sections', 'id')],
            'session_id' => ['nullable', 'integer', Rule::exists('academic_sessions', 'id')],
            'campus_id' => ['nullable', 'integer', Rule::exists('campuses', 'id')],
            'date' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // `all` and an empty string both mean "the whole class"; normalising
        // here keeps the `exists` rule above honest.
        if (in_array($this->input('section_id'), ['all', '', '0', 0], true)) {
            $this->merge(['section_id' => null]);
        }
    }
}
