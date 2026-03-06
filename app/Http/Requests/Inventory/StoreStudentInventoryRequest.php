<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\StudentEnrollmentRecord;

class StoreStudentInventoryRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'campus_id' => 'required|exists:campuses,id',
            'student_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    // Check if student has an active enrollment record for this campus
                    $hasEnrollment = StudentEnrollmentRecord::where('student_id', $value)
                        ->where('campus_id', $this->campus_id)
                        ->active()
                        ->exists();
                    
                    if (!$hasEnrollment) {
                        $fail('The selected student must have an active enrollment in this campus.');
                    }
                },
            ],
            'assigned_date' => 'nullable|date',
        ];

        // Check if multiple items or single item
        if ($this->has('items') && is_array($this->items)) {
            // Multiple items format
            $rules['items'] = 'required|array|min:1';
            $rules['items.*.inventory_item_id'] = [
                'required',
                Rule::exists('inventory_items', 'id')
                    ->where('campus_id', $this->campus_id)
                    ->where('is_active', true),
            ];
            $rules['items.*.quantity'] = 'required|integer|min:1';
            $rules['items.*.discount_amount'] = 'nullable|numeric|min:0';
            $rules['items.*.discount_percentage'] = 'nullable|numeric|min:0|max:100';
        } else {
            // Single item format (backward compatibility)
            $rules['inventory_item_id'] = [
                'required',
                Rule::exists('inventory_items', 'id')
                    ->where('campus_id', $this->campus_id)
                    ->where('is_active', true),
            ];
            $rules['quantity'] = 'required|integer|min:1';
            $rules['discount_amount'] = 'nullable|numeric|min:0';
            $rules['discount_percentage'] = 'nullable|numeric|min:0|max:100';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'quantity.min' => 'Quantity must be at least 1.',
            'items.min' => 'At least one item is required.',
            'items.*.inventory_item_id.exists' => 'The selected item must belong to the campus and be active.',
            'items.*.quantity.min' => 'Quantity must be at least 1 for each item.',
            'inventory_item_id.exists' => 'The selected item must belong to the campus and be active.',
            'student_id.exists' => 'The selected student must belong to the campus.',
            'discount_percentage.max' => 'Discount percentage cannot exceed 100%.',
            'items.*.discount_percentage.max' => 'Discount percentage cannot exceed 100% for any item.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // If both discount types are provided, keep only the percentage (convert amount to percentage if needed)
        if ($this->has('discount_amount') && $this->has('discount_percentage') && $this->discount_amount > 0 && $this->discount_percentage > 0) {
            $this->merge([
                'discount_amount' => null,
            ]);
        }
    }
}
