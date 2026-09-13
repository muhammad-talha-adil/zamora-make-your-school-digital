<?php

namespace App\Http\Requests\Fee;

use App\Enums\Fee\AdjustmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddVoucherAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adjustment_type' => ['required', Rule::in(AdjustmentType::values())],
            'amount' => 'required|numeric|gt:0',
            'description' => 'nullable|string|max:255',
            'related_voucher_id' => 'nullable|exists:fee_vouchers,id',
        ];
    }
}
