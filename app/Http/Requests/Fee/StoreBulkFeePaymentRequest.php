<?php

namespace App\Http\Requests\Fee;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a batch of sibling payments submitted from the "pay for
 * siblings together" flow (#115) - one row per student, each shaped exactly
 * like a normal StoreFeePaymentRequest payload.
 */
class StoreBulkFeePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.student_id' => ['required', 'distinct', 'exists:students,id'],
            'payments.*.payment_date' => ['required', 'date'],
            'payments.*.payment_method' => ['required', 'in:cash,bank,online,jazzcash,easypaisa,cheque'],
            'payments.*.reference_no' => ['nullable', 'string', 'max:100'],
            'payments.*.bank_name' => ['nullable', 'string', 'max:100'],
            'payments.*.received_amount' => ['required', 'numeric', 'min:0'],
            'payments.*.charges' => ['required', 'array', 'min:1'],
            'payments.*.charges.*.voucher_id' => ['required', 'exists:fee_vouchers,id'],
            'payments.*.charges.*.fee_voucher_item_id' => ['required', 'exists:fee_voucher_items,id'],
            'payments.*.charges.*.student_account_charge_id' => ['nullable', 'exists:student_account_charges,id'],
            'payments.*.charges.*.source_module' => ['nullable', 'string', 'max:50'],
            'payments.*.charges.*.amount' => ['required', 'numeric', 'gt:0'],
            'payments.*.remarks' => ['nullable', 'string'],
        ];
    }
}
