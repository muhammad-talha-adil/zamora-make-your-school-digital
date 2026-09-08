<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * The month a report is asked for.
 *
 * Defaults to the current month rather than refusing, because a report opened
 * from a menu carries no period and returning a validation error for that is
 * not useful to anybody.
 */
class AttendanceReportRequest extends FormRequest
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
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'month' => $this->input('month', now()->month),
            'year' => $this->input('year', now()->year),
        ]);
    }

    public function month(): int
    {
        return (int) $this->validated('month');
    }

    public function year(): int
    {
        return (int) $this->validated('year');
    }

    public function startOfMonth(): Carbon
    {
        return Carbon::create($this->year(), $this->month(), 1)->startOfDay();
    }

    public function endOfMonth(): Carbon
    {
        return $this->startOfMonth()->endOfMonth();
    }
}
