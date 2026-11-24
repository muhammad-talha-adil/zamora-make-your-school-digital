<?php

namespace App\Http\Requests\Staff;

use App\Http\Requests\Staff\Concerns\ValidatesStaffFields;
use App\Models\Role;
use App\Models\StaffProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends FormRequest
{
    use ValidatesStaffFields;

    public function authorize(): bool
    {
        return $this->user()?->can('create', StaffProfile::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $excluded = $this->user()?->isSuperAdmin() ? ['developer', 'owner'] : ['developer', 'owner', 'campus_admin'];

        return [
            'name' => 'required|string|max:150',
            'email' => 'nullable|email|max:150|unique:users,email',
            'employee_no' => 'nullable|string|max:50|unique:staff_profiles,employee_no',
            'designation_id' => 'nullable|exists:staff_designations,id',
            'employment_type' => 'required|string|max:50',
            'hire_date' => 'nullable|date|before_or_equal:today',
            'basic_salary' => $this->payRules(true),
            'allowance_amount' => $this->payRules(false),
            'payment_method' => 'required|string|max:50',
            'bank_name' => 'nullable|string|max:150',
            'account_no' => 'nullable|string|max:150',
            'is_active' => 'boolean',
            'cnic' => $this->cnicRules(),
            'phone' => $this->phoneRules(),
            'photo' => ['nullable', 'image', 'max:2048'],
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
            'create_login' => ['nullable', 'boolean'],
            'role' => ['nullable', 'string', Rule::exists('roles', 'name')->where(fn ($q) => $q->where('scope_level', '!=', Role::SCOPE_SELF)->whereNotIn('name', $excluded))],
            'documents' => 'nullable|array',
            'documents.*.kind' => 'required_with:documents|string|max:50',
            'documents.*.title' => 'required_with:documents|string|max:150',
            'documents.*.issued_on' => 'nullable|date',
            'documents.*.expires_on' => 'nullable|date|after:documents.*.issued_on',
            'documents.*.file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
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
        return $this->staffMessages() + [
            'photo.image' => 'Photo must be an image.',
            'photo.max' => 'Photo must be smaller than 2 MB.',
        ];
    }
}
