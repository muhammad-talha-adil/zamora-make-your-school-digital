<?php

namespace App\Http\Requests\Student;

use App\Enums\Fee\FeeStructureStatus;
use App\Models\Fee\FeeStructure;
use App\Models\Guardian;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\StudentStatus;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
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
            'admission_no.unique' => 'The admission number has already been taken by another student.',
            'admission_no.max' => 'The admission number may not be greater than 50 characters.',
            'dob.required' => 'The date of birth is required.',
            'dob.date' => 'The date of birth must be a valid date.',
            'dob.before' => 'The date of birth must be before today.',
            'gender_id.required' => 'Please select a gender.',
            'gender_id.exists' => 'The selected gender is invalid.',
            'student_status_id.required' => 'Please select a student status.',
            'student_status_id.exists' => 'The selected student status is invalid.',
            'b_form.unique' => 'The B-Form/CNIC number has already been registered by another student.',
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
            'section_id.required' => 'Please select a section.',
            'section_id.exists' => 'The selected section is invalid.',
            'status_id.required' => 'Please select a status.',
            'status_id.exists' => 'The selected status is invalid.',
            'status_description.max' => 'The status description may not be greater than 1000 characters.',
            'is_reactivation.boolean' => 'The reactivation flag must be true or false.',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $studentId = $this->route('student')?->id ?? null;

        Log::info('UpdateStudentRequest validation started', [
            'user_id' => auth()->id(),
            'student_id' => $studentId,
            'ip' => request()->ip(),
            'method' => $this->method(),
        ]);

        // Base rules for student update
        $studentRules = [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s\.\-\'\@]+$/',
            ],
            'admission_no' => [
                'required',
                'string',
                'max:50',
                Rule::unique('students', 'admission_no')->ignore($studentId),
            ],
            'dob' => [
                'required',
                'date',
                'before:today',
                function ($attribute, $value, $fail) {
                    $dob = Carbon::parse($value);
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
                Rule::unique('students', 'b_form')->ignore($studentId),
            ],
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

            // Fee Structure Integration Fields
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
            'discounts' => [
                'nullable',
                'array',
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
            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'admission_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,gif,webp',
                'max:2048',
            ],
            'remove_image' => [
                'nullable',
                'boolean',
            ],
        ];

        // Enrollment rules (for active enrollment)
        $enrollmentRules = [
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
            'section_id' => [
                'nullable',
                'integer',
                Rule::exists('sections', 'id'),
            ],
        ];

        $guardianRules = [
            'guardian_id' => [
                'nullable',
                'integer',
                Rule::exists('guardians', 'id'),
            ],
            'father_name' => [
                'required_without:guardian_id',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s\.\-\'\@]+$/',
            ],
            'father_email' => [
                'nullable',
                'string',
                'email',
                'max:255',
            ],
            'father_phone' => [
                'required_without:guardian_id',
                'string',
                'max:20',
                'regex:/^[0-9+\-\s]+$/',
            ],
            'father_cnic' => [
                'nullable',
                'string',
                'max:15',
                'regex:/^[0-9]{5}-[0-9]{7}-[0-9]$/',
                function ($attribute, $value, $fail) use ($studentId) {
                    if (empty($value)) {
                        return;
                    }

                    $currentGuardianIds = Student::with('studentGuardians')
                        ->find($studentId)
                        ?->studentGuardians
                        ->pluck('guardian_id')
                        ->filter()
                        ->all() ?? [];

                    $exists = Guardian::where('cnic', $value)
                        ->when(
                            ! empty($currentGuardianIds),
                            fn ($query) => $query->whereNotIn('id', $currentGuardianIds)
                        )
                        ->exists();

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
            'other_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'other_email' => [
                'nullable',
                'string',
                'email',
                'max:255',
            ],
            'other_phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9+\-\s]+$/',
            ],
            'other_cnic' => [
                'nullable',
                'string',
                'max:15',
                'regex:/^[0-9]{5}-[0-9]{7}-[0-9]$/',
                function ($attribute, $value, $fail) use ($studentId) {
                    if (empty($value)) {
                        return;
                    }

                    $currentGuardianIds = Student::with('studentGuardians')
                        ->find($studentId)
                        ?->studentGuardians
                        ->pluck('guardian_id')
                        ->filter()
                        ->all() ?? [];

                    $exists = Guardian::where('cnic', $value)
                        ->when(
                            ! empty($currentGuardianIds),
                            fn ($query) => $query->whereNotIn('id', $currentGuardianIds)
                        )
                        ->exists();

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

        // Status change rules
        $statusChangeRules = [
            'status_id' => [
                'required_if:is_reactivation,false',
                'exclude_if:is_reactivation,true',
                'integer',
                Rule::exists('student_statuses', 'id'),
            ],
            'status_description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'is_reactivation' => [
                'nullable',
                'boolean',
            ],
        ];

        // Re-admission rules
        $readmitRules = [
            'session_id' => [
                'required_with:class_id,section_id,campus_id',
                'integer',
                Rule::exists('academic_sessions', 'id'),
            ],
            'class_id' => [
                'required_with:session_id,section_id,campus_id',
                'integer',
                Rule::exists('school_classes', 'id'),
            ],
            'section_id' => [
                'required_with:session_id,class_id,campus_id',
                'integer',
                Rule::exists('sections', 'id'),
            ],
            'campus_id' => [
                'required_with:session_id,class_id,section_id',
                'integer',
                Rule::exists('campuses', 'id'),
            ],
            'admission_date' => [
                'nullable',
                'date',
                'after_or_equal:today',
            ],
        ];

        // Guardian update rules
        $guardianRules = [
            'guardians' => [
                'nullable',
                'array',
            ],
            'guardians.*.id' => [
                'required_with:guardians',
                'integer',
                Rule::exists('guardians', 'id'),
            ],
            'guardians.*.relation_id' => [
                'required_with:guardians.*.id',
                'integer',
                Rule::exists('relations', 'id'),
            ],
            'guardians.*.is_primary' => [
                'nullable',
                'boolean',
            ],
            'guardians.*.phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9+\-\s]+$/',
            ],
        ];

        // Determine which rules to apply based on the request action
        $action = $this->get('action', 'update');

        return match ($action) {
            'update' => array_merge($studentRules, $enrollmentRules, $guardianRules),
            'change_status' => $statusChangeRules,
            'readmit' => $readmitRules,
            'update_guardians' => $guardianRules,
            default => array_merge($studentRules, $enrollmentRules),
        };
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $studentId = $this->route('student')?->id;
            $action = $this->get('action', 'update');

            // Only run additional validations for specific actions
            if ($action === 'update') {
                $this->validateUpdateCrossFields($validator, $studentId);
            }

            // Log validation completion
            if ($validator->errors()->isEmpty()) {
                Log::info('UpdateStudentRequest validation passed', [
                    'user_id' => auth()->id(),
                    'student_id' => $studentId,
                    'action' => $action,
                ]);
            } else {
                Log::warning('UpdateStudentRequest validation failed', [
                    'user_id' => auth()->id(),
                    'student_id' => $studentId,
                    'action' => $action,
                    'errors' => $validator->errors()->toArray(),
                ]);
            }
        });
    }

    /**
     * Validate cross-field relationships for update.
     */
    private function validateUpdateCrossFields($validator, ?int $studentId): void
    {
        // Validate section based on class sections
        if ($this->filled('class_id')) {
            $sectionCount = Section::where('class_id', $this->class_id)->count();
            if ($sectionCount > 0 && empty($this->section_id)) {
                $validator->errors()->add(
                    'section_id',
                    'Please select a section for this class.'
                );
            }
        }

        // Validate that section belongs to the selected class
        if ($this->filled('class_id') && $this->filled('section_id')) {
            $sectionExists = Section::where('id', $this->section_id)
                ->where('class_id', $this->class_id)
                ->exists();

            if (! $sectionExists) {
                $validator->errors()->add(
                    'section_id',
                    'The selected section does not belong to the selected class.'
                );
            }
        }

        $fatherPhone = ! empty($this->father_phone)
            ? preg_replace('/\D/', '', $this->father_phone)
            : null;
        $otherPhone = ! empty($this->other_phone)
            ? preg_replace('/\D/', '', $this->other_phone)
            : null;

        if ($fatherPhone && $otherPhone && $fatherPhone === $otherPhone) {
            $validator->errors()->add(
                'other_phone',
                "Other guardian's phone number cannot be the same as Father's phone number."
            );
        }

        if ($this->filled('fee_structure_id')) {
            $feeStructure = FeeStructure::with('items')->find($this->fee_structure_id);

            $structureStatus = $feeStructure?->status instanceof FeeStructureStatus
                ? $feeStructure->status->value
                : $feeStructure?->status;

            if (! $feeStructure || $structureStatus !== FeeStructureStatus::ACTIVE->value) {
                $validator->errors()->add('fee_structure_id', 'The selected fee structure is not active.');
            } elseif (
                (int) $feeStructure->session_id !== (int) $this->session_id ||
                (int) $feeStructure->campus_id !== (int) $this->campus_id
            ) {
                $validator->errors()->add('fee_structure_id', 'The selected fee structure does not belong to the chosen campus/session.');
            } elseif ($feeStructure->class_id !== null && (int) $feeStructure->class_id !== (int) $this->class_id) {
                $validator->errors()->add('fee_structure_id', 'The selected fee structure does not belong to the chosen class.');
            } elseif ($feeStructure->section_id !== null && (int) $feeStructure->section_id !== (int) $this->section_id) {
                $validator->errors()->add('fee_structure_id', 'The selected fee structure does not belong to the chosen section.');
            }

            if ($feeStructure && $this->fee_mode === 'manual') {
                $customEntries = collect($this->custom_fee_entries ?? []);
                $selectedFeeHeadIds = $customEntries->pluck('fee_head_id')->map(fn ($id) => (int) $id)->all();

                if ($customEntries->isEmpty()) {
                    $validator->errors()->add('custom_fee_entries', 'At least one custom fee entry is required for manual mode.');
                }

                $requiredFeeHeadIds = $feeStructure->items
                    ->where('is_optional', false)
                    ->pluck('fee_head_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                foreach ($requiredFeeHeadIds as $feeHeadId) {
                    if (! in_array($feeHeadId, $selectedFeeHeadIds, true)) {
                        $validator->errors()->add('custom_fee_entries', 'All mandatory fee heads must be included in manual mode.');
                        break;
                    }
                }
            }

            if ($feeStructure && $this->fee_mode === 'discount' && empty($this->discounts)) {
                $validator->errors()->add('discounts', 'At least one discount is required when discount mode is selected.');
            }
        }

        // Validate that the student can be re-activated if status is changing
        if ($this->filled('student_status_id')) {
            $currentStatus = StudentStatus::find($this->student_status_id);
            if ($currentStatus && strtolower($currentStatus->name) === 'active') {
                // Check if there's already an active enrollment
                if ($studentId) {
                    $hasActiveEnrollment = StudentEnrollmentRecord::where('student_id', $studentId)
                        ->whereNull('leave_date')
                        ->exists();

                    if (! $hasActiveEnrollment) {
                        // Warn that student has no active enrollment
                        Log::warning('Student being activated without active enrollment', [
                            'student_id' => $studentId,
                            'user_id' => auth()->id(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'student name',
            'admission_no' => 'admission number',
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
            'campus_id' => 'campus',
            'session_id' => 'academic session',
            'class_id' => 'class',
            'section_id' => 'section',
            'description' => 'description',
            'admission_date' => 'admission date',
            'image' => 'student photo',
            'father_name' => "father's/guardian's name",
            'father_email' => "father's email",
            'father_phone' => "father's phone",
            'father_cnic' => "father's CNIC",
            'father_occupation' => "father's occupation",
            'father_address' => "father's address",
            'father_relation_id' => 'relationship to father/guardian',
            'other_name' => "other guardian's name",
            'other_email' => "other guardian's email",
            'other_phone' => "other guardian's phone",
            'other_cnic' => "other guardian's CNIC",
            'other_relation_id' => 'relationship to other guardian',
            'status_id' => 'status',
            'status_description' => 'status description',
            'is_reactivation' => 'reactivation flag',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize the action parameter
        $action = $this->input('action', 'update');

        // Map old action names to new ones for backward compatibility
        $actionMap = [
            'status' => 'change_status',
            'reactivate' => 'readmit',
            'guardians' => 'update_guardians',
        ];

        if (isset($actionMap[$action])) {
            $action = $actionMap[$action];
        }

        $this->merge([
            'action' => $action,
        ]);

        // Trim input fields
        $this->merge([
            'name' => trim($this->name ?? ''),
            'admission_no' => trim($this->admission_no ?? ''),
            'b_form' => trim($this->b_form ?? ''),
            'description' => trim($this->description ?? ''),
            'status_description' => trim($this->status_description ?? ''),
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
        if (is_string($discounts) && ! empty($discounts)) {
            $decoded = json_decode($discounts, true);
            if (is_array($decoded)) {
                $this->merge(['discounts' => $decoded]);
            }
        }

        $customFeeEntries = $this->custom_fee_entries;
        if (is_string($customFeeEntries) && ! empty($customFeeEntries)) {
            $decoded = json_decode($customFeeEntries, true);
            if (is_array($decoded)) {
                $this->merge(['custom_fee_entries' => $decoded]);
            }
        }
    }

    /**
     * Get the validated data for external use.
     *
     * @return array<string, mixed>
     */
    public function validatedForStudent(): array
    {
        return $this->only([
            'admission_no',
            'dob',
            'gender_id',
            'student_status_id',
            'b_form',
        ]);
    }

    /**
     * Get the validated enrollment data for external use.
     *
     * @return array<string, mixed>
     */
    public function validatedForEnrollment(): array
    {
        return $this->only([
            'campus_id',
            'session_id',
            'class_id',
            'section_id',
            'monthly_fee',
            'annual_fee',
            'fee_structure_id',
            'fee_mode',
            'custom_fee_entries',
            'discounts',
            'manual_discount_percentage',
            'manual_discount_reason',
        ]);
    }

    /**
     * Get the validated status change data for external use.
     *
     * @return array<string, mixed>
     */
    public function validatedForStatusChange(): array
    {
        return $this->only([
            'status_id',
            'status_description',
            'is_reactivation',
            'session_id',
            'class_id',
            'section_id',
            'campus_id',
            'admission_date',
        ]);
    }
}
