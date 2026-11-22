<?php

namespace App\Http\Controllers\Fee;

use App\Enums\Fee\PaymentStatus;
use App\Enums\Fee\VoucherStatus;
use App\Http\Controllers\Controller;
use App\Models\Fee\FeePayment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FeeLedgerController extends Controller
{
    /**
     * Read-only fee ledger of one student: vouchers, payments and reversals
     * with a running balance.
     */
    public function show(Request $request, Student $student): Response
    {
        Gate::authorize('viewByStudent', [FeePayment::class, $student->id]);

        $entries = collect();

        $vouchers = $student->feeVouchers()
            ->where('status', '!=', VoucherStatus::CANCELLED->value)
            ->with('voucherMonth')
            ->get();

        foreach ($vouchers as $voucher) {
            $billedOn = sprintf('%04d-%02d-01', $voucher->voucher_year, $voucher->voucherMonth?->month_number ?? 1);
            $label = trim(($voucher->voucherMonth?->name ?? '').' '.$voucher->voucher_year);
            $entries->push([
                'date' => $billedOn,
                'order' => 0,
                'type' => 'Voucher',
                'reference' => $voucher->voucher_no,
                'description' => $label,
                'debit' => (float) $voucher->net_amount,
                'credit' => 0.0,
            ]);

            if ($voucher->status === VoucherStatus::ADJUSTED) {
                $entries->push([
                    'date' => $billedOn,
                    'order' => 1,
                    'type' => 'Carried forward',
                    'reference' => $voucher->voucher_no,
                    'description' => 'Balance moved to a newer voucher',
                    'debit' => 0.0,
                    'credit' => max(0.0, (float) $voucher->net_amount - (float) $voucher->paid_amount),
                ]);
            }
        }

        $payments = FeePayment::query()->where('student_id', $student->id)->get();

        foreach ($payments as $payment) {
            $notes = collect([
                (float) $payment->late_fee_amount > 0 ? 'Late fee '.number_format((float) $payment->late_fee_amount, 2) : null,
                (float) $payment->concession_amount > 0 ? 'Concession '.number_format((float) $payment->concession_amount, 2) : null,
            ])->filter()->implode(', ');

            $entries->push([
                'date' => $payment->payment_date->toDateString(),
                'order' => 1,
                'type' => 'Payment',
                'reference' => $payment->receipt_no,
                'description' => $notes,
                'debit' => 0.0,
                'credit' => (float) $payment->received_amount,
            ]);

            if ($payment->status === PaymentStatus::REVERSED) {
                $entries->push([
                    'date' => $payment->updated_at->toDateString(),
                    'order' => 2,
                    'type' => 'Reversal',
                    'reference' => $payment->receipt_no,
                    'description' => 'Payment reversed',
                    'debit' => (float) $payment->received_amount,
                    'credit' => 0.0,
                ]);
            }
        }

        $balance = 0.0;
        $rows = $entries
            ->sortBy([['date', 'asc'], ['order', 'asc']])
            ->values()
            ->map(function (array $entry) use (&$balance) {
                $balance = round($balance + $entry['debit'] - $entry['credit'], 2);

                return $entry + ['balance' => $balance];
            })
            ->all();

        return Inertia::render('Fee/Ledger/Show', [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'registration_number' => $student->registration_number,
            ],
            'rows' => $rows,
            'closingBalance' => $balance,
        ]);
    }
}
