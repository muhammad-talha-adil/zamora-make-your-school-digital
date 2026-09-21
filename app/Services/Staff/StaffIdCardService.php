<?php

namespace App\Services\Staff;

use App\Models\StaffProfile;
use App\Services\Attendance\QrCodeService;
use Illuminate\Support\Collection;

/**
 * The staff ID card — the same CR80/CNIC-sized print view `IdCardService`
 * builds for students, kept as its own small class rather than folded into
 * that one, since a staff card carries different fields (employee number,
 * designation, department) and nothing here needs a guardian's phone.
 */
class StaffIdCardService
{
    public function __construct(
        private QrCodeService $qrCodes
    ) {}

    /**
     * Cards for a set of staff.
     *
     * @param  Collection<int, StaffProfile>|array<int, StaffProfile>  $staff
     * @return array<int, array<string, mixed>>
     */
    public function forStaff($staff): array
    {
        $staff = StaffProfile::hydrate([])->merge(collect($staff))->loadMissing([
            'user:id,name',
            'campus',
            'designation',
            'department',
        ]);

        return $staff->map(fn (StaffProfile $profile) => [
            'staff' => $profile,
            'campus' => $profile->campus?->name,
            'designation' => $profile->designation?->name,
            'department' => $profile->department?->name,
            // Scanning this marks them present for today — see
            // `QrAttendanceController` / `routes/attendance.php`.
            'attendance_qr' => $this->qrCodes->forStaff($profile),
        ])->values()->all();
    }
}
