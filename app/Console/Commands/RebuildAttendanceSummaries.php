<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\AttendanceStudent;
use App\Services\Attendance\AttendanceSummaryService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Rebuilds the monthly attendance summaries from the registers.
 *
 * The summaries are a cache and nothing else. This is what makes that claim
 * true: any row, or all of them, can be thrown away and reconstructed from
 * `attendance_students`. Needed for the months that were recorded before the
 * summaries were maintained at all, and as the answer whenever a figure is
 * doubted.
 */
class RebuildAttendanceSummaries extends Command
{
    protected $signature = 'attendance:rebuild-summaries
        {--session= : Limit to one academic session}
        {--year= : Limit to one calendar year}
        {--month= : Limit to one month, with --year}';

    protected $description = 'Rebuild monthly attendance summaries from the registers';

    public function handle(AttendanceSummaryService $summaries): int
    {
        $query = Attendance::query()
            ->select('session_id', 'attendance_date')
            ->when($this->option('session'), fn ($q, $session) => $q->where('session_id', $session))
            ->when($this->option('year'), fn ($q, $year) => $q->whereYear('attendance_date', $year))
            ->when($this->option('month'), fn ($q, $month) => $q->whereMonth('attendance_date', $month));

        // One summary per student per month, so the registers are reduced to
        // the distinct months they belong to before anything is recomputed.
        $periods = $query->get()
            ->map(fn (Attendance $register) => [
                'session_id' => (int) $register->session_id,
                'month' => (int) Carbon::parse($register->attendance_date)->month,
                'year' => (int) Carbon::parse($register->attendance_date)->year,
            ])
            ->unique(fn (array $period) => implode('-', $period))
            ->values();

        if ($periods->isEmpty()) {
            $this->info('No registers found for the given options.');

            return self::SUCCESS;
        }

        $this->info('Rebuilding '.$periods->count().' student-month period(s).');
        $bar = $this->output->createProgressBar($periods->count());
        $rebuilt = 0;

        foreach ($periods as $period) {
            $rebuilt += $this->rebuildPeriod($summaries, $period);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Rebuilt {$rebuilt} summary row(s).");

        return self::SUCCESS;
    }

    /**
     * @param  array{session_id: int, month: int, year: int}  $period
     */
    private function rebuildPeriod(AttendanceSummaryService $summaries, array $period): int
    {
        $from = Carbon::create($period['year'], $period['month'], 1)->startOfDay();
        $to = $from->copy()->endOfMonth();

        $studentIds = AttendanceStudent::query()
            ->whereHas('attendance', function ($query) use ($from, $to, $period) {
                $query->where('session_id', $period['session_id'])
                    ->whereDate('attendance_date', '>=', $from->toDateString())
                    ->whereDate('attendance_date', '<=', $to->toDateString());
            })
            ->distinct()
            ->pluck('student_id');

        $rebuilt = 0;

        foreach ($studentIds as $studentId) {
            if ($summaries->refresh((int) $studentId, $period['session_id'], $period['month'], $period['year'])) {
                $rebuilt++;
            }
        }

        return $rebuilt;
    }
}
