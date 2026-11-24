<?php

namespace App\Services\Fee;

use App\Enums\Fee\VoucherItemSource;
use App\Enums\Fee\VoucherStatus;
use App\Models\Fee\FeeVoucher;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Single source of truth for what a student owes.
 *
 * Definitions (every Fee screen must use these, never its own query):
 * - Open voucher: status unpaid / partial / overdue with balance > 0. Vouchers
 *   carried forward (status `adjusted`) are excluded because their balance now
 *   lives, once, as an arrears line on the newer voucher.
 * - Outstanding: sum of balance_amount of open vouchers.
 * - Overdue: for a voucher whose due date has passed, its whole balance. For a
 *   voucher not yet due, only its arrears (carried-forward) part, because that
 *   is old money from earlier months.
 * - Not yet due: outstanding minus overdue.
 * - Overdue since: earliest due date of the truly overdue source. Arrears take
 *   the due date of the earliest carried-forward voucher, not the new voucher's.
 * - Days overdue: whole days between that date and today (0 when nothing overdue).
 */
class StudentBalanceService
{
    /**
     * @return array{outstanding: float, overdue: float, not_yet_due: float, overdue_since: ?string, days_overdue: int, voucher_count: int}
     */
    public function forStudent(int $studentId): array
    {
        return $this->forStudents(FeeVoucher::query()->where('student_id', $studentId))[$studentId] ?? $this->empty();
    }

    /**
     * @param  Builder<FeeVoucher>  $scope  Already limited by campus/class/visibility.
     * @return array<int, array{outstanding: float, overdue: float, not_yet_due: float, overdue_since: ?string, days_overdue: int, voucher_count: int}>
     */
    public function forStudents($scope): array
    {
        $vouchers = (clone $scope)
            ->whereIn('status', [VoucherStatus::UNPAID, VoucherStatus::PARTIAL, VoucherStatus::OVERDUE])
            ->where('balance_amount', '>', 0)
            ->with(['items' => fn ($q) => $q->where('source_type', VoucherItemSource::ARREARS->value)])
            ->get();

        if ($vouchers->isEmpty()) {
            return [];
        }

        $studentIds = $vouchers->pluck('student_id')->unique()->all();
        $carriedDates = FeeVoucher::query()
            ->whereIn('student_id', $studentIds)
            ->where('status', VoucherStatus::ADJUSTED->value)
            ->whereDate('due_date', '<', today())
            ->selectRaw('student_id, MIN(due_date) as earliest')
            ->groupBy('student_id')
            ->pluck('earliest', 'student_id');

        $result = [];
        foreach ($vouchers->groupBy('student_id') as $studentId => $group) {
            $row = $this->empty();
            $dates = [];
            foreach ($group as $voucher) {
                $balance = (float) $voucher->balance_amount;
                $row['outstanding'] += $balance;
                $row['voucher_count']++;
                $due = $voucher->due_date ? Carbon::parse($voucher->due_date)->startOfDay() : null;

                if ($due && $due->lt(today())) {
                    $row['overdue'] += $balance;
                    $dates[] = $due;

                    continue;
                }

                $arrears = min($balance, (float) $voucher->items->sum('net_amount'));
                if ($arrears > 0) {
                    $row['overdue'] += $arrears;
                    $dates[] = isset($carriedDates[$studentId])
                        ? Carbon::parse($carriedDates[$studentId])->startOfDay()
                        : today()->subDay();
                }
            }

            if ($dates !== []) {
                $earliest = collect($dates)->min();
                $row['overdue_since'] = $earliest->toDateString();
                $row['days_overdue'] = max(0, (int) $earliest->diffInDays(today()));
            }

            $row['outstanding'] = round($row['outstanding'], 2);
            $row['overdue'] = round(min($row['overdue'], $row['outstanding']), 2);
            $row['not_yet_due'] = round($row['outstanding'] - $row['overdue'], 2);
            $result[(int) $studentId] = $row;
        }

        return $result;
    }

    /**
     * @return array{outstanding: float, overdue: float, not_yet_due: float, overdue_since: ?string, days_overdue: int, voucher_count: int}
     */
    protected function empty(): array
    {
        return ['outstanding' => 0.0, 'overdue' => 0.0, 'not_yet_due' => 0.0, 'overdue_since' => null, 'days_overdue' => 0, 'voucher_count' => 0];
    }
}
