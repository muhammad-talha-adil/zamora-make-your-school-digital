<?php

namespace App\Http\Requests\Fee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFamilyFeePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'exists:students,id'],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'payment_method' => ['required', 'in:cash,bank,online,jazzcash,easypaisa,cheque'],
            'reference_no' => [
                'nullable',
                'string',
                'max:100',
                Rule::requiredIf($this->input('payment_method') !== 'cash'),
                Rule::prohibitedIf($this->input('payment_method') === 'cash'),
            ],
            'bank_name' => [
                'nullable',
                'string',
                'max:100',
                Rule::requiredIf(in_array($this->input('payment_method'), ['bank', 'cheque'], true)),
            ],
            'remarks' => ['nullable', 'string'],
            'vouchers' => ['required', 'array', 'min:1'],
            'vouchers.*.voucher_id' => ['required', 'distinct', 'exists:fee_vouchers,id'],
            'vouchers.*.amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_date.before_or_equal' => 'Payment date cannot be in the future.',
            'vouchers.required' => 'Select at least one voucher.',
            'vouchers.min' => 'Select at least one voucher.',
            'vouchers.*.amount.gt' => 'Amount must be more than zero.',
        ];
    }
}
