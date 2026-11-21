<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
            'name' => ['required', 'string', 'max:255'],
            'description' => 'nullable|string',
            // Owner/developer only - a campus-restricted creator never
            // submits this; the controller creates one class row per campus
            // chosen here (or just their own campus otherwise). Per-campus
            // name uniqueness is checked in the controller, since it must be
            // checked once per selected campus, not once for the request.
            'campus_ids' => 'nullable|array',
            'campus_ids.*' => 'integer|exists:campuses,id',
        ];
    }
}
