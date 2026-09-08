<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\AttendancePolicy;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Closes registers that the school has had long enough to correct.
 *
 * The lock worked but had to be operated by hand, so registers stayed editable
 * for ever — and a register that can still be changed a year later is not a
 * record of anything.
 *
 * Each campus sets its own window on `attendance_policies.lock_after_days`, and
 * zero — the default — closes nothing, so a school that has not asked for this
 * sees no change. Reopening a closed register stays a deliberate act by a
 * campus admin or above.
 */
class LockSettledAttendanceRegisters extends Command
{
    protected $signature = 'attendance:lock-settled
        {--campus= : Limit to one campus}
        {--dry-run : Show what would be locked without locking it}';

    protected $description = 'Lock attendance registers older than each campus\'s correction window';

    public function handle(): int
    {
        $registers = Attendance::query()
            ->unlocked()
            ->when($this->option('campus'), fn ($q, $campus) => $q->where('campus_id', $campus))
            ->orderBy('attendance_date')
            ->get();

        if ($registers->isEmpty()) {
            $this->info('Nothing to lock.');

            return self::SUCCESS;
        }

        $policies = [];
        $locked = 0;

        foreach ($registers as $register) {
            // Resolved once per campus and session: a fee run of a whole school
            // would otherwise ask the same question a thousand times.
            $key = $register->campus_id.':'.$register->session_id;
            $policy = $policies[$key] ??= AttendancePolicy::resolve($register->campus_id, $register->session_id);

            if (! $policy->shouldAutoLock(Carbon::parse($register->attendance_date))) {
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line(sprintf(
                    '  would lock #%d — %s, class %s',
                    $register->id,
                    Carbon::parse($register->attendance_date)->toDateString(),
                    $register->class_id
                ));
            } else {
                $register->lock();
            }

            $locked++;
        }

        $this->info($this->option('dry-run')
            ? "{$locked} register(s) would be locked."
            : "Locked {$locked} register(s).");

        return self::SUCCESS;
    }
}
