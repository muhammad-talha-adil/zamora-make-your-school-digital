<?php

namespace App\Http\Requests\Fee;

use App\Enums\Fee\FeeFrequency;
use App\Enums\Fee\FeeHeadCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeeHeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $feeHeadId = $this->route('fee_head');

        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', Rule::unique('fee_heads', 'code')->ignore($feeHeadId)],
            'category' => ['required', Rule::enum(FeeHeadCategory::class)],
            'is_recurring' => ['boolean'],
            'default_frequency' => ['required', Rule::enum(FeeFrequency::class)],
            'is_optional' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Fee head name is required.',
            'code.required' => 'Fee head code is required.',
            'code.unique' => 'This fee head code already exists.',
            'category.required' => 'Please select a category.',
            'default_frequency.required' => 'Please select a frequency.',
        ];
    }
}
