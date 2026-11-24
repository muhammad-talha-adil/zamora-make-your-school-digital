<?php

namespace App\Http\Requests\Fee;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SaveDiscountTypeRequest extends FormRequest
{
    public const MAX_FIXED_AMOUNT = 10000000;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'default_value_type' => ['required', 'in:fixed,percent'],
            'default_value' => ['required', 'numeric', 'min:0'],
            'requires_approval' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $value = $this->input('default_value');

            if (! is_numeric($value)) {
                return;
            }

            if ($this->input('default_value_type') === 'percent' && (float) $value > 100) {
                $validator->errors()->add('default_value', 'A percentage cannot be more than 100.');
            }

            if ($this->input('default_value_type') === 'fixed' && (float) $value > self::MAX_FIXED_AMOUNT) {
                $validator->errors()->add('default_value', 'Amount cannot be more than Rs 10,000,000.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a name.',
            'default_value.required' => 'Please enter a value.',
            'default_value.numeric' => 'Value must be a number.',
            'default_value.min' => 'Value cannot be negative.',
            'default_value_type.required' => 'Please choose percentage or fixed amount.',
        ];
    }
}
