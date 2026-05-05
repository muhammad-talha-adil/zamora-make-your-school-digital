<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
        $schoolClassId = $this->route('schoolClass')->id;

        return [
            'name' => 'required|string|max:255|unique:school_classes,name,'.$schoolClassId,
            'code' => 'required|string|max:255|unique:school_classes,code,'.$schoolClassId,
            'description' => 'nullable|string',
        ];
    }
}
