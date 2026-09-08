<?php

namespace App\Http\Requests\Fee;

use App\Enums\Fee\FeeFrequency;
use App\Enums\Fee\FeeStructureStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Rules for creating a fee structure and its charges.
 *
 * These used to sit inline in the controller and covered only the title,
 * scope, fee head and amount — the columns that decide *when* a charge is
 * billed had no rules at all, so a bad month range or frequency reached the
 * database unchecked.
 */
class StoreFeeStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorisation is handled by the controller using policies.
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'session_id' => ['required', 'integer', Rule::exists('academic_sessions', 'id')],
            'campus_id' => ['required', 'integer', Rule::exists('campuses', 'id')],
            'class_id' => ['nullable', 'integer', Rule::exists('school_classes', 'id')],

            // One structure can be created for several sections at once.
            'section_ids' => ['nullable', 'array'],
            'section_ids.*' => ['integer', Rule::exists('sections', 'id')],

            'status' => ['required', Rule::enum(FeeStructureStatus::class)],
            'is_default' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['nullable', 'array'],
            'items.*.fee_head_id' => ['required_with:items', 'integer', Rule::exists('fee_heads', 'id')],
            'items.*.amount' => ['required_with:items', 'numeric', 'min:0', 'decimal:0,2'],

            // Frequency decides whether a charge repeats every month, once a
            // year, or once ever. It defaults to the fee head's own setting.
            'items.*.frequency' => ['nullable', Rule::enum(FeeFrequency::class)],

            'items.*.is_optional' => ['nullable', 'boolean'],
            'items.*.applicable_on_admission' => ['nullable', 'boolean'],
            'items.*.is_transport_related' => ['nullable', 'boolean'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],

            // A charge may run for part of the year only, e.g. August to June.
            'items.*.starts_from_month_id' => ['nullable', 'integer', Rule::exists('months', 'id')],
            'items.*.ends_at_month_id' => ['nullable', 'integer', Rule::exists('months', 'id')],
            'items.*.billing_month_id' => ['nullable', 'integer', Rule::exists('months', 'id')],
            'items.*.billing_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Please give the fee structure a title.',
            'session_id.required' => 'Please select an academic session.',
            'campus_id.required' => 'Please select a campus.',
            'items.*.fee_head_id.required_with' => 'Each charge needs a fee head.',
            'items.*.amount.required_with' => 'Each charge needs an amount.',
            'items.*.amount.min' => 'A charge cannot be negative.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('items', []) as $index => $item) {
                // A section-limited charge must have both ends of the range, or
                // neither; one alone has no meaning.
                $from = $item['starts_from_month_id'] ?? null;
                $to = $item['ends_at_month_id'] ?? null;

                if (($from && ! $to) || ($to && ! $from)) {
                    $validator->errors()->add(
                        "items.{$index}.starts_from_month_id",
                        'Give both a start and an end month, or leave both blank.'
                    );
                }
            }

            // The same fee head twice in one structure would bill it twice.
            $feeHeadIds = collect($this->input('items', []))->pluck('fee_head_id')->filter();

            if ($feeHeadIds->count() !== $feeHeadIds->unique()->count()) {
                $validator->errors()->add('items', 'Each fee head may only appear once in a structure.');
            }
        });
    }
}
