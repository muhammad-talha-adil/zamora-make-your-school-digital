<?php

namespace App\Services\Attendance;

use App\Models\AttendanceDevice;
use App\Models\AttendanceDeviceIdentity;
use App\Models\AttendanceDevicePunch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Takes gate punches in and turns them into attendance.
 *
 * Most schools of any size already have a thumb scanner at the gate, and its
 * log is typed into a register by hand or not used at all.
 *
 * **This is the whole of the backend and deliberately none of the driver.**
 * Every make of scanner speaks its own protocol, and choosing one before the
 * school has bought a machine is guesswork. Whatever eventually reads the
 * device hands `import()` a plain list of punches; everything from there is
 * built and tested.
 *
 * Two rules run through it:
 *
 *  - **A punch is never edited.** It is stored as reported, and what the system
 *    made of it is recorded beside it. A punch that matched nobody stays as the
 *    evidence that a card is unregistered.
 *  - **Importing twice imports once.** Re-reading a machine's log is the normal
 *    way of recovering from a failure, so the same punch arriving again is
 *    recognised rather than doubled.
 */
class AttendancePunchImporter
{
    /**
     * Stores a batch of punches from a device.
     *
     * @param  array<int, array{identifier: string, punched_at: string, direction?: string|null}>  $punches
     * @return array{stored: int, duplicates: int, unmatched: int}
     */
    public function import(AttendanceDevice $device, array $punches): array
    {
        $stored = 0;
        $duplicates = 0;
        $unmatched = 0;

        DB::transaction(function () use ($device, $punches, &$stored, &$duplicates, &$unmatched) {
            foreach ($punches as $punch) {
                $identifier = trim((string) ($punch['identifier'] ?? ''));

                if ($identifier === '' || empty($punch['punched_at'])) {
                    continue;
                }

                $punchedAt = Carbon::parse($punch['punched_at']);

                $already = AttendanceDevicePunch::where('device_id', $device->id)
                    ->where('identifier', $identifier)
                    ->where('punched_at', $punchedAt)
                    ->exists();

                if ($already) {
                    $duplicates++;

                    continue;
                }

                $identity = $this->identify($identifier, $device->device_type);

                AttendanceDevicePunch::create([
                    'device_id' => $device->id,
                    'identifier' => $identifier,
                    'punched_at' => $punchedAt,
                    'direction' => $device->directionFor($punch['direction'] ?? null),
                    'student_id' => $identity?->student_id,
                    'staff_profile_id' => $identity?->staff_profile_id,
                    'status' => $identity
                        ? AttendanceDevicePunch::STATUS_PENDING
                        : AttendanceDevicePunch::STATUS_UNMATCHED,
                    'note' => $identity ? null : 'No active identity registered for this identifier',
                ]);

                $stored++;

                if (! $identity) {
                    $unmatched++;
                }
            }

            $device->update(['last_seen_at' => now()]);
        });

        return ['stored' => $stored, 'duplicates' => $duplicates, 'unmatched' => $unmatched];
    }

    /**
     * The person a machine's identifier belongs to.
     */
    public function identify(string $identifier, string $type = 'biometric'): ?AttendanceDeviceIdentity
    {
        return AttendanceDeviceIdentity::query()
            ->active()
            ->where('identifier', $identifier)
            ->where('identity_type', $type)
            ->first();
    }

    /**
     * The first arrival and last departure each person made on a date.
     *
     * A child passes the gate more than once a day — out at break, back in —
     * and the register wants the two ends of that, not every crossing. This is
     * what a screen or a later job would read to fill in check-in and check-out
     * times; it is deliberately a **read**, leaving the decision to write a
     * register with the part of the system that owns registers.
     *
     * @return array<int, array{student_id: int, first_in: string|null, last_out: string|null}>
     */
    public function dailySummary(Carbon $date, ?int $campusId = null): array
    {
        $punches = AttendanceDevicePunch::query()
            ->whereNotNull('student_id')
            ->whereDate('punched_at', $date->toDateString())
            ->whereIn('status', [AttendanceDevicePunch::STATUS_PENDING, AttendanceDevicePunch::STATUS_APPLIED])
            ->when($campusId, fn ($q) => $q->whereHas('device', fn ($d) => $d->where('campus_id', $campusId)))
            ->orderBy('punched_at')
            ->get()
            ->groupBy('student_id');

        return $punches->map(function ($rows, $studentId) {
            $ins = $rows->filter(fn ($row) => $row->direction !== AttendanceDevice::READS_OUT);
            $outs = $rows->filter(fn ($row) => $row->direction === AttendanceDevice::READS_OUT);

            return [
                'student_id' => (int) $studentId,
                'first_in' => $ins->first()?->punched_at?->format('H:i'),
                // Where a machine reports no direction at all, the last punch of
                // the day is the departure — which is how a single gate machine
                // is read everywhere.
                'last_out' => ($outs->last() ?? ($rows->count() > 1 ? $rows->last() : null))
                    ?->punched_at?->format('H:i'),
            ];
        })->values()->all();
    }
}
