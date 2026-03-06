<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;

class UpdateInventoryTypeRequest extends FormRequest
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
        return [
            'campus_id' => 'required',
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'original_name' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'An inventory type with this name already exists for the selected campus.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $campusId = $this->input('campus_id');
            $name = $this->input('name');
            $originalName = trim($this->input('original_name', ''));
            $inventoryTypeId = Route::current()->parameter('inventoryType');

            // Get the original inventory type
            $originalType = \App\Models\InventoryType::find($inventoryTypeId);

            // Validate campus_id: either 'all' or numeric ID that exists
            if ($campusId !== 'all') {
                if (!is_numeric($campusId) || !\App\Models\Campus::where('id', $campusId)->exists()) {
                    $validator->errors()->add('campus_id', 'The selected campus id is invalid.');
                    return;
                }
            }

            /**
             * CASE C: Name Temporarily Modified During Edit Session
             * If the name is the same as the original (case-insensitive), 
             * it's the same entity being edited - allow without uniqueness check
             */
            if ($originalName && strtolower(trim($name)) === strtolower($originalName)) {
                // Name hasn't actually changed - skip uniqueness validation
                return;
            }

            /**
             * CASE B: Name Changed - Validate uniqueness
             * Check if the new name already exists in the selected campus
             * Exclude the current type ID from the check
             */
            if ($campusId === 'all') {
                // Check if name exists in ANY campus (excluding current type)
                $existingType = \App\Models\InventoryType::whereRaw('LOWER(name) = LOWER(?)', [trim($name)])
                    ->where('id', '!=', $inventoryTypeId)
                    ->exists();
            } else {
                // Check if name exists in the specific campus
                $existingInCampus = \App\Models\InventoryType::where('campus_id', $campusId)
                    ->whereRaw('LOWER(name) = LOWER(?)', [trim($name)])
                    ->where('id', '!=', $inventoryTypeId)
                    ->exists();
                
                // Also check if there's an "All Campuses" type with the same name
                $existingInAllCampuses = \App\Models\InventoryType::whereNull('campus_id')
                    ->whereRaw('LOWER(name) = LOWER(?)', [trim($name)])
                    ->where('id', '!=', $inventoryTypeId)
                    ->exists();
                
                $existingType = $existingInCampus || $existingInAllCampuses;
                
                if ($existingInAllCampuses) {
                    $validator->errors()->add('name', 'An inventory type with this name already exists for All Campuses. Cannot create duplicate for specific campus.');
                    return;
                }
            }

            if ($existingType) {
                $validator->errors()->add('name', 'An inventory type with this name already exists for the selected campus.');
            }
        });
    }
}
