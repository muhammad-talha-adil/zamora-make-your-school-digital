<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolClassRequest extends FormRequest
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
        $schoolClass = $this->route('schoolClass');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('school_classes', 'name')
                    ->ignore($schoolClass->id)
                    ->where('campus_id', $schoolClass->campus_id)
                    ->whereNull('deleted_at'),
            ],
            'description' => 'nullable|string',
        ];
    }
}
