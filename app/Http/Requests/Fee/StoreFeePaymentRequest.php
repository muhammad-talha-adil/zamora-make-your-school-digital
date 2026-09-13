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
            'payment_date' => 'required|date',
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
            'received_amount' => 'required|numeric|min:0',
            'charges' => 'required|array|min:1',
            'charges.*.voucher_id' => 'required|exists:fee_vouchers,id',
            'charges.*.fee_voucher_item_id' => 'required|exists:fee_voucher_items,id',
            'charges.*.student_account_charge_id' => 'nullable|exists:student_account_charges,id',
            'charges.*.source_module' => 'nullable|string|max:50',
            'charges.*.amount' => 'required|numeric|gt:0',
            'remarks' => 'nullable|string',
        ];
    }
}
