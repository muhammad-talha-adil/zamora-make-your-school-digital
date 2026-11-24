<?php

namespace App\Http\Requests\Staff;

use App\Models\StaffDesignation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create and edit of a designation share one rule set; the unique name rule
 * ignores the designation being edited.
 */
class SaveDesignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $designation = $this->route('designation');

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('staff_designations', 'name')->ignore($designation instanceof StaffDesignation ? $designation->id : null),
            ],
            'description' => 'nullable|string|max:255',
            'permission_template' => ['nullable', 'array'],
            'permission_template.*' => ['string', 'exists:permissions,name'],
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'name.unique' => 'A position with this name already exists.',
            'name.max' => 'Name can be at most 100 letters.',
            'permission_template.*.exists' => 'One of the chosen permissions no longer exists.',
        ];
    }
}
