<?php

namespace App\Http\Requests\Staff;

use App\Http\Requests\Staff\Concerns\ValidatesStaffFields;
use App\Models\StaffProfile;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffPersonalRequest extends FormRequest
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

        $dob = ['nullable', 'date', 'before:today'];
        if ($profile->hire_date) {
            $dob[] = 'before_or_equal:'.$profile->hire_date->toDateString();
        }

        return [
            'cnic' => $this->cnicRules($profile),
            'phone' => $this->phoneRules($profile),
            'dob' => $dob,
            'gender_id' => ['nullable', 'integer', 'exists:genders,id'],
            'blood_group' => ['nullable', 'string', 'max:5'],
            'address' => ['nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:50'],
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
