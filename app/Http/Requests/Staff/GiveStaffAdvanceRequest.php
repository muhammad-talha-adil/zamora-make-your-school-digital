<?php

namespace App\Http\Requests\Staff;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GiveStaffAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'staff_profile_id' => ['required', 'exists:staff_profiles,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'disbursed_date' => ['required', 'date'],
            'monthly_deduction_amount' => ['nullable', 'numeric', 'min:0.01', 'lte:amount'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
