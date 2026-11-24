<?php

namespace App\Http\Controllers\Fee;

use App\Enums\Fee\PaymentStatus;
use App\Enums\Fee\VoucherStatus;
use App\Http\Controllers\Controller;
use App\Models\Fee\FeePayment;
use App\Models\Fee\FeePaymentAllocation;
use App\Models\Student;
use App\Services\Fee\StudentBalanceService;
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

        $firstPaymentOn = FeePaymentAllocation::query()
            ->whereIn('fee_voucher_id', $vouchers->pluck('id'))
            ->join('new_fee_payments', 'new_fee_payments.id', '=', 'fee_payment_allocations.fee_payment_id')
            ->groupBy('fee_payment_allocations.fee_voucher_id')
            ->selectRaw('fee_payment_allocations.fee_voucher_id as voucher_id, MIN(DATE(new_fee_payments.payment_date)) as first_date')
            ->pluck('first_date', 'voucher_id');

        $postedOn = [];

        foreach ($vouchers as $voucher) {
            $billedOn = sprintf('%04d-%02d-01', $voucher->voucher_year, $voucher->voucherMonth?->month_number ?? 1);
            $postedOn[$voucher->id] = collect([$billedOn, $voucher->created_at?->toDateString(), $firstPaymentOn->get($voucher->id)])
                ->filter()
                ->min();
        }

        foreach ($vouchers as $voucher) {
            $label = trim(($voucher->voucherMonth?->name ?? '').' '.$voucher->voucher_year);
            $entries->push([
                'date' => $postedOn[$voucher->id],
                'bucket' => 0,
                'id' => $voucher->id,
                'sub' => 0,
                'type' => 'Voucher',
                'reference' => $voucher->voucher_no,
                'description' => $label,
                'debit' => (float) $voucher->net_amount,
                'credit' => 0.0,
            ]);

            if ($voucher->status === VoucherStatus::ADJUSTED) {
                $carriedOn = $postedOn[$voucher->id];
                if (preg_match('/carried forward to voucher (\S+?)\.?(\s|$)/', (string) $voucher->notes, $match)) {
                    $newer = $vouchers->firstWhere('voucher_no', $match[1]);
                    if ($newer) {
                        $carriedOn = max($carriedOn, $postedOn[$newer->id]);
                    }
                }

                $entries->push([
                    'date' => $carriedOn,
                    'bucket' => 2,
                    'id' => $voucher->id,
                    'sub' => 0,
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

            $isReversed = $payment->status === PaymentStatus::REVERSED;

            $applied = round(
                (float) $payment->received_amount - (float) $payment->excess_amount
                - ($isReversed ? (float) $payment->late_fee_amount - (float) $payment->concession_amount : 0),
                2
            );
            $applied = max(0.0, $applied);

            $paidOn = $payment->payment_date->toDateString();

            $entries->push([
                'date' => $paidOn,
                'bucket' => 1,
                'id' => $payment->id,
                'sub' => 0,
                'type' => 'Payment',
                'reference' => $payment->receipt_no,
                'description' => $notes,
                'debit' => 0.0,
                'credit' => $applied,
            ]);

            if ($isReversed) {
                $entries->push([
                    'date' => max($paidOn, $payment->updated_at->toDateString()),
                    'bucket' => 1,
                    'id' => $payment->id,
                    'sub' => 1,
                    'type' => 'Reversal',
                    'reference' => $payment->receipt_no,
                    'description' => 'Payment reversed',
                    'debit' => $applied,
                    'credit' => 0.0,
                ]);
            }
        }

        $balance = 0.0;
        $rows = $entries
            ->sortBy([['date', 'asc'], ['bucket', 'asc'], ['id', 'asc'], ['sub', 'asc']])
            ->values()
            ->map(function (array $entry) use (&$balance) {
                $balance = round($balance + $entry['debit'] - $entry['credit'], 2);
                unset($entry['bucket'], $entry['id'], $entry['sub']);

                return $entry + ['balance' => $balance];
            })
            ->all();

        $balances = app(StudentBalanceService::class)->forStudent($student->id);

        return Inertia::render('Fee/Ledger/Show', [
            'balances' => $balances,
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
