<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * A family, or the office, asking for a child's leave.
 */
class ApplyStudentLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The controller decides whose child this is.
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'integer', Rule::exists('leave_types', 'id')->where('is_active', true)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'description' => ['required', 'string', 'min:5', 'max:1000'],
            'attachment_path' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $from = $this->date('start_date');
            $to = $this->date('end_date');

            if (! $from || ! $to) {
                return;
            }

            /*
             * A month is the longest a family may ask for in one application.
             * Anything longer is a child leaving the school for a while, which
             * is a different conversation and a different record.
             */
            if ($from->diffInDays($to) > 31) {
                $validator->errors()->add('end_date', 'Apply for at most one month at a time.');
            }

            // Asking in the middle of last term for days already marked helps
            // nobody: the register for those days has been taken.
            if ($to->lessThan(Carbon::today()->subDays(30))) {
                $validator->errors()->add(
                    'start_date',
                    'Leave cannot be applied for more than a month in the past.'
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'description.required' => 'Say why the leave is needed.',
            'description.min' => 'Give a little more detail than that.',
            'end_date.after_or_equal' => 'The last day cannot be before the first.',
        ];
    }
}
