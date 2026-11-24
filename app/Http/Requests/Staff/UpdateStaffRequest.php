<?php

namespace App\Http\Requests\Staff;

use App\Http\Requests\Staff\Concerns\ValidatesStaffFields;
use App\Models\StaffProfile;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffRequest extends FormRequest
{
    use ValidatesStaffFields;

    public function authorize(): bool
    {
        $profile = $this->route('staffProfile');

        return $profile instanceof StaffProfile && ($this->user()?->can('update', $profile) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var StaffProfile $profile */
        $profile = $this->route('staffProfile');

        $hireDate = ['nullable', 'date', 'before_or_equal:today'];
        if ($profile->dob) {
            $hireDate[] = 'after_or_equal:'.$profile->dob->toDateString();
        }

        return [
            'name' => 'required|string|max:150',
            'email' => 'nullable|email|max:150|unique:users,email,'.$profile->user_id,
            'employee_no' => 'required|string|max:50|unique:staff_profiles,employee_no,'.$profile->id,
            'campus_id' => 'nullable|exists:campuses,id',
            'department_id' => 'nullable|exists:staff_departments,id',
            'designation_id' => 'nullable|exists:staff_designations,id',
            'employment_type' => 'required|string|max:50',
            'hire_date' => $hireDate,
            'basic_salary' => $this->payRules(true),
            'allowance_amount' => $this->payRules(false),
            'deduction_amount' => $this->payRules(false),
            'payment_method' => 'required|string|max:50',
            'bank_name' => 'nullable|string|max:150',
            'account_no' => 'nullable|string|max:150',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->staffAttributes();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->staffMessages();
    }
}
