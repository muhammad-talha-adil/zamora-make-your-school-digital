<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSchoolClassRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Unique per campus, not school-wide — "Class 9" exists once
                // per campus now, so two campuses can each have their own.
                Rule::unique('school_classes', 'name')
                    ->where('campus_id', $this->user()->campusId())
                    ->whereNull('deleted_at'),
            ],
            'description' => 'nullable|string',
        ];
    }
}
