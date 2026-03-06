<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

class UpdateInventoryItemRequest extends FormRequest
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
        $inventoryItem = Route::current()->parameter('inventoryItem');

        return [
            'campus_id' => 'required|exists:campuses,id',
            'inventory_type_id' => 'required|exists:inventory_types,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('inventory_items', 'name')
                    ->where('campus_id', $this->campus_id)
                    ->where('inventory_type_id', $this->inventory_type_id)
                    ->ignore($inventoryItem),
            ],
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'An inventory item with this name already exists for the selected campus and type.',
        ];
    }
}
