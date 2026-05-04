<?php

namespace App\Http\Requests\Student;

use App\Models\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by the controller using policies
        return true;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'admission_no.required' => 'The admission number is required.',
            'admission_no.unique' => 'The admission number has already been taken.',
            'admission_no.max' => 'The admission number may not be greater than 50 characters.',
            'name.required' => 'The student name is required.',
            'name.max' => 'The student name may not be greater than 255 characters.',
            'dob.required' => 'The date of birth is required.',
            'dob.date' => 'The date of birth must be a valid date.',
            'dob.before' => 'The date of birth must be before today.',
            'gender_id.required' => 'Please select a gender.',
            'gender_id.exists' => 'The selected gender is invalid.',
            'student_status_id.required' => 'Please select a student status.',
            'student_status_id.exists' => 'The selected student status is invalid.',
            'b_form.unique' => 'The B-Form/CNIC number has already been registered.',
            'b_form.max' => 'The B-Form/CNIC number may not be greater than 15 characters.',
            'b_form.min' => 'The B-Form/CNIC number must be at least 13 characters.',
            'monthly_fee.numeric' => 'The monthly fee must be a number.',
            'monthly_fee.min' => 'The monthly fee must be at least 0.',
            'annual_fee.numeric' => 'The annual fee must be a number.',
            'annual_fee.min' => 'The annual fee must be at least 0.',
            'campus_id.required' => 'Please select a campus.',
            'campus_id.exists' => 'The selected campus is invalid.',
            'session_id.required' => 'Please select an academic session.',
            'session_id.exists' => 'The selected academic session is invalid.',
            'class_id.required' => 'Please select a class.',
            'class_id.exists' => 'The selected class is invalid.',
            'section_id.exists' => 'The selected section is invalid.',
            'guardian_id.exists' => 'The selected guardian is invalid.',
            'father_name.required' => 'The father/guardian name is required.',
            'father_name.max' => 'The father/guardian name may not be greater than 255 characters.',
            'father_email.email' => 'The father email must be a valid email address.',
            'father_email.max' => 'The father email may not be greater than 255 characters.',
            'father_phone.max' => 'The father phone may not be greater than 20 characters.',
            'father_cnic.max' => 'The father CNIC may not be greater than 15 characters.',
            'father_cnic.regex' => 'The father CNIC format is invalid.',
            'father_occupation.max' => 'The father occupation may not be greater than 255 characters.',
            'father_relation_id.required' => 'Please select the relationship to father/guardian.',
            'father_relation_id.exists' => 'The selected relationship is invalid.',
            'other_name.max' => 'The other guardian name may not be greater than 255 characters.',
            'other_email.email' => 'The other guardian email must be a valid email address.',
            'other_email.max' => 'The other guardian email may not be greater than 255 characters.',
            'other_phone.max' => 'The other guardian phone may not be greater than 20 characters.',
            'other_cnic.max' => 'The other guardian CNIC may not be greater than 15 characters.',
            'other_relation_id.exists' => 'The selected other guardian relationship is invalid.',
            'student_email.email' => 'The student email must be a valid email address.',
            'student_email.max' => 'The student email may not be greater than 255 characters.',
            'student_email.unique' => 'The student email has already been taken.',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        Log::info('StoreStudentRequest validation started', [
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
        ]);

        return [
            // Student basic info
            'admission_no' => [
                'required',
                'string',
                'max:50',
                Rule::unique('students', 'admission_no'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s\.\-\'\@]+$/',
            ],
            'student_email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'dob' => [
                'required',
                'date',
                'before:today',
                // Ensure student is at least 3 years old and not more than 25 years old
                function ($attribute, $value, $fail) {
                    $dob = \Carbon\Carbon::parse($value);
                    $minAge = 3;
                    $maxAge = 25;

                    if ($dob->age < $minAge) {
                        $fail("The student must be at least {$minAge} years old.");
                    }

                    if ($dob->age > $maxAge) {
                        $fail("The student cannot be older than {$maxAge} years.");
                    }
                },
            ],
            'gender_id' => [
                'required',
                'integer',
                Rule::exists('genders', 'id'),
            ],
            'student_status_id' => [
                'required',
                'integer',
                Rule::exists('student_statuses', 'id'),
            ],
            'b_form' => [
                'nullable',
                'string',
                'max:15',
                'min:13',
                'regex:/^[0-9]{5}-[0-9]{7}-[0-9]$/',
                Rule::unique('students', 'b_form'),
            ],

            // Fee info (stored on enrollment record)
            'monthly_fee' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
            'annual_fee' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],

            // NEW: Fee Structure Integration Fields
            'fee_structure_id' => [
                'nullable',
                'integer',
                Rule::exists('fee_structures', 'id'),
            ],
            'fee_mode' => [
                'nullable',
                'string',
                'in:structure,discount,manual',
            ],
            'custom_fee_entries' => [
                'nullable',
                'array',
            ],
            'custom_fee_entries.*.fee_head_id' => [
                'required_with:custom_fee_entries',
                'integer',
                Rule::exists('fee_heads', 'id'),
            ],
            'custom_fee_entries.*.amount' => [
                'required_with:custom_fee_entries',
                'numeric',
                'min:0',
            ],
            'discounts' => [
                'nullable',
                'array',
            ],
            'discounts.*.discount_type_id' => [
                'required_with:discounts',
                'integer',
                Rule::exists('discount_types', 'id'),
            ],
            'discounts.*.fee_head_id' => [
                'required_with:discounts',
                'integer',
                Rule::exists('fee_heads', 'id'),
            ],
            'discounts.*.value' => [
                'required_with:discounts',
                'numeric',
                'min:0',
            ],
            'discounts.*.value_type' => [
                'required_with:discounts',
                'string',
                'in:fixed,percent',
            ],
            'manual_discount_percentage' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'manual_discount_reason' => [
                'nullable',
                'string',
                'max:500',
            ],

            // Admission fee info
            'admission_fee' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
            'payment_status' => [
                'nullable',
                'string',
                'in:pending,paid,waived',
            ],
            'fee_notes' => [
                'nullable',
                'string',
                'max:500',
            ],

            // Enrollment info (required)
            'campus_id' => [
                'required',
                'integer',
                Rule::exists('campuses', 'id'),
            ],
            'session_id' => [
                'required',
                'integer',
                Rule::exists('academic_sessions', 'id'),
            ],
            'class_id' => [
                'required',
                'integer',
                Rule::exists('school_classes', 'id'),
            ],
            // Section is optional - only required if sections exist for the class
            // Validation will be handled in withValidator based on class sections
            'section_id' => [
                'nullable',
                'integer',
                Rule::exists('sections', 'id'),
            ],

            // Guardian ID (optional - for linking existing guardian)
            'guardian_id' => [
                'nullable',
                'integer',
                Rule::exists('guardians', 'id'),
            ],

            // Father Guardian - Required if no guardian_id
            'father_name' => [
                'required_without:guardian_id',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s\.\-\'\@]+$/',
            ],
            'father_email' => [
                'nullable',
                'string',
                'email:rfc,dns',
                'max:255',
            ],
            'father_phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9+\-\s]+$/',
            ],
            'father_cnic' => [
                'nullable',
                'string',
                'max:15',
                'regex:/^[0-9]{5}-[0-9]{7}-[0-9]$/',
                // CNIC should be unique across guardians (unless linking existing guardian)
                function ($attribute, $value, $fail) {
                    if (empty($value)) {
                        return;
                    }
                    
                    // Skip uniqueness check if guardian_id is provided (linking existing guardian)
                    if (!empty($this->guardian_id)) {
                        return;
                    }
                    
                    $exists = \App\Models\Guardian::where('cnic', $value)->exists();
                    if ($exists) {
                        $fail('This CNIC has already been registered for another guardian.');
                    }
                },
            ],
            'father_occupation' => [
                'nullable',
                'string',
                'max:255',
            ],
            'father_address' => [
                'nullable',
                'string',
                'max:500',
            ],
            'father_relation_id' => [
                'required_with:father_name',
                'integer',
                Rule::exists('relations', 'id'),
            ],

            // Other Guardian - Optional (nullable, only validated if provided)
            'other_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'other_email' => [
                'nullable',
                'string',
                'email:rfc,dns',
                'max:255',
            ],
            'other_phone' => [
                'nullable',
                'string',
                'max:20',
            ],
            'other_cnic' => [
                'nullable',
                'string',
                'max:15',
                // CNIC should be unique across guardians
                function ($attribute, $value, $fail) {
                    if (empty($value)) {
                        return;
                    }
                    
                    $exists = \App\Models\Guardian::where('cnic', $value)->exists();
                    if ($exists) {
                        $fail('This CNIC has already been registered for another guardian.');
                    }
                },
            ],
            'other_relation_id' => [
                'nullable',
                'integer',
                Rule::exists('relations', 'id'),
            ],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Cross-field validation: Phone numbers must be unique across guardians
            $fatherPhone = !empty($this->father_phone)
                ? preg_replace('/\D/', '', $this->father_phone)
                : null;
            $otherPhone = !empty($this->other_phone)
                ? preg_replace('/\D/', '', $this->other_phone)
                : null;

            $phoneErrors = [];

            if ($fatherPhone && $otherPhone && $fatherPhone === $otherPhone) {
                $validator->errors()->add(
                    'other_phone',
                    "Other guardian's phone number cannot be the same as Father's phone number."
                );
            }

            // Validate section_id based on class sections
            if (!empty($this->class_id) && $this->section_id === null) {
                $sectionCount = \App\Models\Section::where('class_id', $this->class_id)->count();
                if ($sectionCount > 0) {
                    $validator->errors()->add(
                        'section_id',
                        'Please select a section for this class.'
                    );
                }
            }

            // Validate that section belongs to the selected class
            if (!empty($this->class_id) && !empty($this->section_id)) {
                $sectionExists = \App\Models\Section::where('id', $this->section_id)
                    ->where('class_id', $this->class_id)
                    ->exists();

                if (!$sectionExists) {
                    $validator->errors()->add(
                        'section_id',
                        'The selected section does not belong to the selected class.'
                    );
                }
            }

            // Log validation completion
            if ($validator->errors()->isEmpty()) {
            Log::info('StoreStudentRequest validation passed', [
                'user_id' => auth()->id(),
                'admission_no' => $this->admission_no,
            ]);
            } else {
            Log::warning('StoreStudentRequest validation failed', [
                'user_id' => auth()->id(),
                'errors' => $validator->errors()->toArray(),
            ]);
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'admission_no' => 'admission number',
            'student_email' => 'student email',
            'dob' => 'date of birth',
            'gender_id' => 'gender',
            'student_status_id' => 'student status',
            'b_form' => 'B-Form/CNIC',
            'monthly_fee' => 'monthly fee',
            'annual_fee' => 'annual fee',
            'fee_structure_id' => 'fee structure',
            'fee_mode' => 'fee mode',
            'custom_fee_entries' => 'custom fee entries',
            'custom_fee_entries.*.fee_head_id' => 'fee head',
            'custom_fee_entries.*.amount' => 'custom amount',
            'discounts' => 'discounts',
            'discounts.*.discount_type_id' => 'discount type',
            'discounts.*.fee_head_id' => 'discount fee head',
            'discounts.*.value' => 'discount value',
            'discounts.*.value_type' => 'discount value type',
            'manual_discount_percentage' => 'discount percentage',
            'manual_discount_reason' => 'discount reason',
            'admission_fee' => 'admission fee',
            'payment_status' => 'payment status',
            'fee_notes' => 'fee notes',
            'campus_id' => 'campus',
            'session_id' => 'academic session',
            'class_id' => 'class',
            'section_id' => 'section',
            'guardian_id' => 'guardian',
            'father_name' => "father's/guardian's name",
            'father_email' => "father's email",
            'father_phone' => "father's phone",
            'father_cnic' => "father's CNIC",
            'father_occupation' => "father's occupation",
            'father_address' => "father's address",
            'father_relation_id' => "relationship to father/guardian",
            'other_name' => "other guardian's name",
            'other_email' => "other guardian's email",
            'other_phone' => "other guardian's phone",
            'other_cnic' => "other guardian's CNIC",
            'other_relation_id' => "relationship to other guardian",
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim and sanitize input
        $this->merge([
            'admission_no' => trim($this->admission_no ?? ''),
            'name' => trim($this->name ?? ''),
            'student_email' => trim($this->student_email ?? ''),
            'father_name' => trim($this->father_name ?? ''),
            'father_email' => trim($this->father_email ?? ''),
            'father_phone' => trim($this->father_phone ?? ''),
            'father_cnic' => trim($this->father_cnic ?? ''),
            'father_occupation' => trim($this->father_occupation ?? ''),
            'father_address' => trim($this->father_address ?? ''),
            'other_name' => trim($this->other_name ?? ''),
            'other_email' => trim($this->other_email ?? ''),
            'other_phone' => trim($this->other_phone ?? ''),
            'other_cnic' => trim($this->other_cnic ?? ''),
        ]);

        // Decode JSON strings sent from frontend via FormData
        // FormData sends JSON as string, so we need to decode it before validation
        $discounts = $this->discounts;
        if (is_string($discounts) && !empty($discounts)) {
            $decoded = json_decode($discounts, true);
            if (is_array($decoded)) {
                $this->merge(['discounts' => $decoded]);
            }
        }

        $customFeeEntries = $this->custom_fee_entries;
        if (is_string($customFeeEntries) && !empty($customFeeEntries)) {
            $decoded = json_decode($customFeeEntries, true);
            if (is_array($decoded)) {
                $this->merge(['custom_fee_entries' => $decoded]);
            }
        }
    }
}
