<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BulkStoreSchoolClassRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Per-campus/per-name uniqueness is checked in the controller, not here
     * - it needs to skip an existing (campus, name) pair rather than fail
     * the whole batch over one already-created class.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'class_names' => ['required', 'array', 'min:1'],
            'class_names.*' => ['required', 'string', 'max:255'],
            // Owner/developer only - a campus-restricted creator never
            // submits this; the controller falls back to their own campus.
            'campus_ids' => ['nullable', 'array'],
            'campus_ids.*' => ['integer', 'exists:campuses,id'],
        ];
    }
}
