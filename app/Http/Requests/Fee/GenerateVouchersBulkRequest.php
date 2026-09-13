<?php

namespace App\Http\Requests\Fee;

use Illuminate\Foundation\Http\FormRequest;

class GenerateVouchersBulkRequest extends FormRequest
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
            'month_ids' => 'required|array|min:1',
            'month_ids.*' => 'required|exists:months,id',
            'year' => 'required|integer|min:2020|max:2100',
            'include_previous_unpaid' => 'nullable|boolean',
            'include_inventory_dues' => 'nullable|boolean',
            'include_transport_dues' => 'nullable|boolean',
        ];
    }
}
