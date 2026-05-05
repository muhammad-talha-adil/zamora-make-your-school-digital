<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'campus_id' => 'required|exists:campuses,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'note' => 'nullable|string|max:1000',
            'purchase_items' => 'required|array|min:1',
            'purchase_items.*.inventory_item_id' => [
                'required',
                'integer',
                Rule::exists('inventory_items', 'id')->where('campus_id', $this->campus_id),
            ],
            'purchase_items.*.quantity' => 'required|integer|min:1',
            'purchase_items.*.purchase_rate' => 'required|numeric|min:0',
            'purchase_items.*.sale_rate' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Please select a supplier.',
            'supplier_id.exists' => 'The selected supplier does not exist.',
            'purchase_items.required' => 'At least one purchase item is required.',
            'purchase_items.min' => 'At least one purchase item is required.',
            'purchase_items.*.inventory_item_id.exists' => 'The selected item does not belong to the selected campus.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('purchase_items') && is_string($this->purchase_items)) {
            $this->merge([
                'purchase_items' => json_decode($this->purchase_items, true),
            ]);
        }
    }
}
