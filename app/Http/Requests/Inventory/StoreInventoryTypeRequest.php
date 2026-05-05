<?php

namespace App\Http\Requests\Inventory;

use App\Models\Campus;
use App\Models\InventoryType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryTypeRequest extends FormRequest
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
            'campus_id' => 'required',
            'name' => 'required|string|max:255',
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

            // Skip validation if campus_id is 'all' (handled in controller)
            if ($campusId === 'all') {
                return;
            }

            // Validate campus_id exists in database
            if (! is_numeric($campusId) || ! Campus::where('id', $campusId)->exists()) {
                $validator->errors()->add('campus_id', 'The selected campus id is invalid.');

                return;
            }

            // Check for duplicate name within the specific campus
            if (InventoryType::where('campus_id', $campusId)
                ->where('name', $name)
                ->exists()) {
                $validator->errors()->add('name', 'An inventory type with this name already exists for the selected campus.');

                return;
            }

            // Also check if there's an "All Campuses" type with the same name
            // If "All Campuses" type exists, no specific campus can have the same name
            if (InventoryType::whereNull('campus_id')
                ->whereRaw('LOWER(name) = LOWER(?)', [trim($name)])
                ->exists()) {
                $validator->errors()->add('name', 'An inventory type with this name already exists for All Campuses. Cannot create duplicate for specific campus.');
            }
        });
    }
}
