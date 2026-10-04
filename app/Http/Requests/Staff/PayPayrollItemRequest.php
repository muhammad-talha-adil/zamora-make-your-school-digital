<?php

namespace App\Http\Requests\Staff;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PayPayrollItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', 'max:50'],
            'reference_no' => ['nullable', 'string', 'max:150'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }

    /**
     * An item may never be paid more than it still owes — the amount, if
     * given, is capped at `amountDue()`.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $amount = $this->input('amount');
            $item = $this->route('payrollRunItem');

            if ($amount !== null && $amount !== '' && $item && (float) $amount > $item->amountDue()) {
                $validator->errors()->add('amount', 'The amount may not exceed what is still due on this item.');
            }
        });
    }
}
