<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceStatusCode;
use App\Models\Attendance;
use App\Models\AttendanceAbsenceAlert;
use App\Models\AttendancePolicy;
use App\Models\AttendanceStudent;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Telling a guardian, the same morning, that their child is not in school.
 *
 * The most expected thing an attendance module does here, and everything it
 * needed was already on hand: the guardian's phone, the absence, and the moment
 * the register was taken.
 *
 * This records what is owed and hands it to a gateway. The gateway itself is
 * configuration — it defaults to the log, so the pipeline is complete and
 * testable before a vendor is chosen and nothing has to be rewritten when one
 * is. Recording first and sending second is deliberate: a message that failed
 * to send is a row the office can see and retry, not a silence.
 */
class AbsenceAlertService
{
    /**
     * Records an alert for every absent child on a register.
     *
     * Only absence counts. A child on approved leave is expected to be away and
     * their family already knows; a late arrival is in school.
     *
     * @return int how many alerts were newly recorded
     */
    public function recordForRegister(Attendance $attendance): int
    {
        $policy = AttendancePolicy::resolve($attendance->campus_id, $attendance->session_id);

        if (! $policy->absence_alert_enabled) {
            return 0;
        }

        $date = Carbon::parse($attendance->attendance_date);

        $absentees = AttendanceStudent::with(['attendanceStatus', 'student.guardians'])
            ->where('attendance_id', $attendance->id)
            ->get()
            ->filter(fn (AttendanceStudent $row) => (bool) $row->attendanceStatus?->hasCode(AttendanceStatusCode::ABSENT));

        $recorded = 0;

        foreach ($absentees as $row) {
            if ($this->record($row, $date)) {
                $recorded++;
            }
        }

        return $recorded;
    }

    /**
     * One alert per child per day, however often the register is saved.
     */
    private function record(AttendanceStudent $row, Carbon $date): bool
    {
        $existing = AttendanceAbsenceAlert::where('student_id', $row->student_id)
            ->whereDate('absence_date', $date->toDateString())
            ->first();

        if ($existing) {
            return false;
        }

        $guardian = $this->guardianFor($row->student);

        AttendanceAbsenceAlert::create([
            'student_id' => $row->student_id,
            'attendance_student_id' => $row->id,
            'absence_date' => $date->toDateString(),
            'guardian_id' => $guardian?->id,
            'recipient_phone' => $guardian?->phone,
            'message' => $this->message($row->student, $date),

            // No number to send to is not a failure to send; it is a family
            // whose contact details need filling in, and the office should see
            // it as exactly that.
            'status' => $guardian?->phone
                ? AttendanceAbsenceAlert::STATUS_PENDING
                : AttendanceAbsenceAlert::STATUS_SKIPPED,
            'failure_reason' => $guardian?->phone ? null : 'No guardian phone number on record',
        ]);

        return true;
    }

    /**
     * The guardian the school rings: the primary one, or any if none is marked.
     */
    private function guardianFor(?Student $student)
    {
        if (! $student) {
            return null;
        }

        $links = $student->studentGuardians()->with('guardian')->get();

        $primary = $links->firstWhere('is_primary', true);

        return ($primary ?? $links->first())?->guardian;
    }

    private function message(?Student $student, Carbon $date): string
    {
        $name = $student?->name ?: 'Your child';

        return $name.' was marked absent from school on '.$date->format('d M Y')
            .'. Please contact the school office if this is unexpected.';
    }

    /**
     * Hands the pending alerts to the gateway.
     *
     * @return array{sent: int, failed: int}
     */
    public function dispatchPending(int $limit = 200): array
    {
        $sent = 0;
        $failed = 0;

        AttendanceAbsenceAlert::pending()
            ->limit($limit)
            ->get()
            ->each(function (AttendanceAbsenceAlert $alert) use (&$sent, &$failed) {
                try {
                    $this->send($alert);
                    $alert->markSent();
                    $sent++;
                } catch (\Throwable $e) {
                    $alert->markFailed($e->getMessage());
                    $failed++;
                }
            });

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * The gateway.
     *
     * `log` is the default and writes the message where it can be read back.
     * A real provider is added here, behind the same call, without touching
     * anything that records or reads the alerts.
     *
     * @throws \RuntimeException
     */
    private function send(AttendanceAbsenceAlert $alert): void
    {
        if (! $alert->recipient_phone) {
            throw new \RuntimeException('No guardian phone number on record');
        }

        match (config('attendance.alerts.driver', 'log')) {
            'log' => Log::channel(config('attendance.alerts.log_channel', 'stack'))
                ->info('Absence alert', [
                    'student_id' => $alert->student_id,
                    'phone' => $alert->recipient_phone,
                    'message' => $alert->message,
                ]),

            default => throw new \RuntimeException(
                'No absence alert driver configured for ['.config('attendance.alerts.driver').']'
            ),
        };
    }
}
