<?php

namespace App\Http\Requests\Settings;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSessionRequest extends FormRequest
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
                Rule::unique('academic_sessions', 'name')->whereNull('deleted_at'),
            ],
            'is_active' => 'boolean',
            'start_year' => 'required|integer|min:2000|max:2100',
            'end_year' => 'required|integer|min:2000|max:2100|gte:start_year',
            'start_date' => 'required|date',
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $start = strtotime((string) $this->input('start_date'));
                    $end = strtotime((string) $value);

                    if ($start && $end && $end > strtotime('+13 months', $start)) {
                        $fail('A session can be at most 1 year long.');
                    }
                },
            ],
            // Owner/developer only (see SessionController::store()) - a
            // campus-restricted creator never submits this, so it is left
            // optional here and the controller fills it in from their own
            // campus instead.
            'campus_ids' => 'nullable|array',
            'campus_ids.*' => 'integer|exists:campuses,id',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'This session already exists.',
            'end_date.after_or_equal' => 'End date must be after the start date.',
        ];
    }
}
