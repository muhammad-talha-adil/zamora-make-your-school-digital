<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BulkStoreSectionRequest extends FormRequest
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
     * Per-class/per-name uniqueness is checked in the controller, not here -
     * it needs to skip an existing (class, name) pair rather than fail the
     * whole batch over one already-created section.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'class_ids' => ['required', 'array', 'min:1'],
            'class_ids.*' => ['integer', 'exists:school_classes,id'],
            'section_names' => ['required', 'array', 'min:1'],
            'section_names.*' => ['required', 'string', 'max:255'],
        ];
    }
}
