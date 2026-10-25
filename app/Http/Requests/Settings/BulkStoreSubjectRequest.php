<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BulkStoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Existing-name skipping is done in the controller, not here - it needs
     * to skip one already-used name rather than fail the whole batch.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject_names' => ['required', 'array', 'min:1'],
            'subject_names.*' => ['required', 'string', 'max:255'],
            'campus_ids' => ['nullable', 'array'],
            'campus_ids.*' => ['integer', 'exists:campuses,id'],
        ];
    }
}
