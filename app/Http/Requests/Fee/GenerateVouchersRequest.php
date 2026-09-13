<?php

namespace App\Http\Requests\Fee;

use Illuminate\Foundation\Http\FormRequest;

class GenerateVouchersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_id' => 'required|exists:academic_sessions,id',
            'campus_id' => 'required|exists:campuses,id',
            'class_id' => 'required|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'month_ids' => 'required|array|min:1',
            'month_ids.*' => 'required|exists:months,id',
            'year' => 'required|integer|min:2020|max:2100',
            'include_previous_unpaid' => 'nullable|boolean',
            'include_inventory_dues' => 'nullable|boolean',
            'include_transport_dues' => 'nullable|boolean',
            'custom_fee_heads' => 'nullable|array',
            'custom_fee_heads.*.fee_head_id' => 'required|exists:fee_heads,id',
            'custom_fee_heads.*.amount' => 'required|numeric|gt:0',
        ];
    }
}
