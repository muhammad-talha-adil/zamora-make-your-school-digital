<?php

namespace App\Http\Requests\Student;

use App\Models\StudentDocumentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentDocumentRequest extends FormRequest
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
     * A document type flagged `is_required` must actually have a file
     * attached — a required paper filed with no attachment is otherwise
     * indistinguishable from one never filed at all.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isRequiredType = StudentDocumentType::find($this->input('student_document_type_id'))?->is_required ?? false;

        return [
            'student_document_type_id' => ['required', 'integer', 'exists:student_document_types,id'],
            'issue_date' => ['nullable', 'date'],
            // A lapsed certificate is what a school is caught out by, and
            // nobody notices it in a folder.
            'expiry_date' => ['nullable', 'date', 'after:issue_date'],
            'file' => [$isRequiredType ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'A file is required for this document type.',
        ];
    }
}
