<?php

namespace App\Http\Requests\Fee;

use App\Enums\Fee\FeeFrequency;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Rules for adding or changing one charge on an existing fee structure.
 */
class SaveFeeStructureItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorisation is handled by the route middleware and controller.
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fee_head_id' => ['required', 'integer', Rule::exists('fee_heads', 'id')],
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999.99', 'decimal:0,2'],
            'frequency' => ['nullable', Rule::enum(FeeFrequency::class)],
            'is_optional' => ['nullable', 'boolean'],
            'applicable_on_admission' => ['nullable', 'boolean'],
            'is_transport_related' => ['nullable', 'boolean'],
            'starts_from_month_id' => ['nullable', 'integer', Rule::exists('months', 'id')],
            'ends_at_month_id' => ['nullable', 'integer', Rule::exists('months', 'id')],
            'billing_month_id' => ['nullable', 'integer', Rule::exists('months', 'id')],
            'billing_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fee_head_id.required' => 'Please pick a fee head.',
            'amount.required' => 'Please enter an amount.',
            'amount.min' => 'An amount cannot be negative.',
            'amount.max' => 'An amount cannot be more than 9,999,999.99.',
            'amount.decimal' => 'Amount can have at most 2 decimal places.',
            'amount.numeric' => 'Amount must be a number.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $from = $this->input('starts_from_month_id');
            $to = $this->input('ends_at_month_id');

            if (($from && ! $to) || ($to && ! $from)) {
                $validator->errors()->add('starts_from_month_id', 'Give both a start and an end month, or leave both blank.');
            }
        });
    }
}
