<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAdmissionEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The public admission-enquiry form — no authenticated user to check.
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'student_name' => ['required', 'string', 'max:255'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            // The one thing a walk-in conversation reliably produces.
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'student_name.required' => 'Please enter the child\'s name.',
            'phone.required' => 'Please enter a phone number we can reach you on.',
            'phone.regex' => 'Please enter a valid phone number.',
            'email.email' => 'Please enter a valid email address.',
        ];
    }
}
