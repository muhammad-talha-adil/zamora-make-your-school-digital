<?php

namespace App\Http\Requests\Staff\Concerns;

use App\Models\StaffProfile;
use App\Support\Cnic;
use Closure;

/**
 * The staff fields every form shares (Create, Employment, Personal): one set
 * of rules, labels and friendly messages, so a rule is never fixed in one
 * form and forgotten in the next.
 */
trait ValidatesStaffFields
{
    public const MAX_PAY = 10000000;

    /**
     * @return array<int, string|Closure>
     */
    protected function cnicRules(?StaffProfile $ignore = null): array
    {
        return [
            'nullable', 'string', 'regex:'.Cnic::PATTERN,
            $this->uniqueAmongStaff('cnic', 'CNIC', $ignore),
        ];
    }

    /**
     * @return array<int, string|Closure>
     */
    protected function phoneRules(?StaffProfile $ignore = null): array
    {
        return [
            'nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/',
            $this->uniqueAmongStaff('phone', 'phone number', $ignore),
        ];
    }

    protected function uniqueAmongStaff(string $column, string $label, ?StaffProfile $ignore): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($column, $label, $ignore): void {
            if ($value === null || $value === '') {
                return;
            }

            $existing = StaffProfile::query()
                ->with('user:id,name')
                ->where($column, $value)
                ->when($ignore, fn ($query) => $query->where('id', '!=', $ignore->id))
                ->first();

            if ($existing) {
                $fail("A staff member with this {$label} already exists: ".($existing->user?->name ?? $existing->employee_no));
            }
        };
    }

    /**
     * @return array<int, string>
     */
    protected function payRules(bool $required): array
    {
        return [$required ? 'required' : 'nullable', 'numeric', 'min:0', 'max:'.self::MAX_PAY];
    }

    /**
     * @return array<string, string>
     */
    protected function staffAttributes(): array
    {
        return [
            'name' => 'Name',
            'email' => 'Email',
            'employee_no' => 'Employee number',
            'cnic' => 'CNIC',
            'phone' => 'Phone',
            'dob' => 'Date of birth',
            'hire_date' => 'Hire date',
            'basic_salary' => 'Basic salary',
            'allowance_amount' => 'Allowance',
            'deduction_amount' => 'Deduction',
            'payment_method' => 'Payment method',
            'employment_type' => 'Employment type',
            'designation_id' => 'Position',
            'campus_id' => 'Campus',
            'emergency_contact_phone' => 'Emergency contact phone',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function staffMessages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'employment_type.required' => 'Employment type is required.',
            'payment_method.required' => 'Payment method is required.',
            'employee_no.required' => 'Employee number is required.',
            'employee_no.unique' => 'This employee number is already used by another staff member.',
            'email.unique' => 'This email is already used by another account.',
            'email.email' => 'Enter a valid email, like name@school.com.',
            'cnic.regex' => 'CNIC must be 13 digits, e.g. '.Cnic::EXAMPLE,
            'phone.regex' => 'Phone can only have digits, + and -.',
            'dob.date' => 'Enter a valid date of birth.',
            'dob.before' => 'Date of birth must be before today.',
            'dob.before_or_equal' => 'Date of birth cannot be after the hire date.',
            'hire_date.date' => 'Enter a valid hire date.',
            'hire_date.before_or_equal' => 'Hire date cannot be in the future.',
            'hire_date.after_or_equal' => 'Hire date cannot be before the date of birth.',
            'basic_salary.required' => 'Basic salary is required.',
            'basic_salary.numeric' => 'Basic salary must be a number.',
            'basic_salary.min' => 'Basic salary cannot be negative.',
            'basic_salary.max' => 'Basic salary cannot be more than Rs 10,000,000.',
            'allowance_amount.numeric' => 'Allowance must be a number.',
            'allowance_amount.min' => 'Allowance cannot be negative.',
            'allowance_amount.max' => 'Allowance cannot be more than Rs 10,000,000.',
            'deduction_amount.min' => 'Deduction cannot be negative.',
            'deduction_amount.max' => 'Deduction cannot be more than Rs 10,000,000.',
        ];
    }
}
