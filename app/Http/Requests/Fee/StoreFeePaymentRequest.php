<?php

namespace App\Http\Requests\Fee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:students,id',
            'payment_date' => 'required|date|before_or_equal:today',
            'payment_method' => 'required|in:cash,bank,online,jazzcash,easypaisa,cheque',
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
            'received_amount' => 'required|numeric|gt:0|max:10000000',
            'charges' => 'required|array|min:1',
            'charges.*.voucher_id' => 'required|exists:fee_vouchers,id',
            'charges.*.fee_voucher_item_id' => 'required|exists:fee_voucher_items,id',
            'charges.*.student_account_charge_id' => 'nullable|exists:student_account_charges,id',
            'charges.*.source_module' => 'nullable|string|max:50',
            'charges.*.amount' => 'required|numeric|min:0|max:10000000',
            'late_fee' => 'nullable|numeric|min:0|decimal:0,2',
            'concession' => 'nullable|numeric|min:0|decimal:0,2',
            'concession_reason' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => (float) $this->input('concession') > 0)],
            'remarks' => 'nullable|string',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'received_amount.required' => 'Enter the amount received.',
            'received_amount.gt' => 'Amount must be greater than 0.',
            'received_amount.min' => 'Negative amount is not allowed.',
            'received_amount.max' => 'Amount is too large. The most you can enter is Rs 10,000,000.',
            'received_amount.numeric' => 'Enter a valid amount.',
            'payment_method.required' => 'Choose how the money was received.',
            'charges.*.amount.min' => 'Negative amount is not allowed.',
            'charges.*.amount.max' => 'Amount is too large.',
            'charges.required' => 'Tick at least one due item to pay.',
            'payment_date.before_or_equal' => 'Payment date cannot be in the future.',
            'late_fee.min' => 'Late fee cannot be negative.',
            'late_fee.decimal' => 'Late fee can have up to 2 decimals.',
            'concession.min' => 'Concession cannot be negative.',
            'concession.decimal' => 'Concession can have up to 2 decimals.',
            'concession_reason.required' => 'Give a reason for the concession.',
        ];
    }
}
