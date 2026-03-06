<?php

namespace App\Http\Requests\Student;

use App\Models\Student;
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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
            'update' => array_merge($studentRules, $enrollmentRules),
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
            $sectionCount = \App\Models\Section::where('class_id', $this->class_id)->count();
            if ($sectionCount > 0 && empty($this->section_id)) {
                $validator->errors()->add(
                    'section_id',
                    'Please select a section for this class.'
                );
            }
        }

        // Validate that section belongs to the selected class
        if ($this->filled('class_id') && $this->filled('section_id')) {
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

        // Validate that the student can be re-activated if status is changing
        if ($this->filled('student_status_id')) {
            $currentStatus = \App\Models\StudentStatus::find($this->student_status_id);
            if ($currentStatus && strtolower($currentStatus->name) === 'active') {
                // Check if there's already an active enrollment
                if ($studentId) {
                    $hasActiveEnrollment = \App\Models\StudentEnrollmentRecord::where('student_id', $studentId)
                        ->whereNull('leave_date')
                        ->exists();

                    if (!$hasActiveEnrollment) {
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
            'admission_no' => 'admission number',
            'dob' => 'date of birth',
            'gender_id' => 'gender',
            'student_status_id' => 'student status',
            'b_form' => 'B-Form/CNIC',
            'monthly_fee' => 'monthly fee',
            'annual_fee' => 'annual fee',
            'campus_id' => 'campus',
            'session_id' => 'academic session',
            'class_id' => 'class',
            'section_id' => 'section',
            'status_id' => 'status',
            'status_description' => 'status description',
            'is_reactivation' => 'reactivation flag',
            'admission_date' => 'admission date',
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
            'admission_no' => trim($this->admission_no ?? ''),
            'b_form' => trim($this->b_form ?? ''),
            'status_description' => trim($this->status_description ?? ''),
        ]);
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
