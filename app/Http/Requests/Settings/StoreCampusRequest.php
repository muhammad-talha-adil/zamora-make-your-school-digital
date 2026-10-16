<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampusRequest extends FormRequest
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
                Rule::unique('campuses', 'name')->whereNull('deleted_at'),
            ],
            'address' => 'nullable|string',
            'is_active' => 'boolean',
            // Nullable: a quick "add campus" modal elsewhere in the app (the
            // fee structure form) only asks for a name — the controller
            // defaults this to the first campus type rather than making
            // every quick-add flow carry a type picker too.
            'campus_type_id' => 'nullable|exists:campus_types,id',
        ];
    }
}
