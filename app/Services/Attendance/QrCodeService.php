<?php

namespace App\Services\Attendance;

use App\Models\StaffProfile;
use App\Models\Student;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\URL;

/**
 * Turns a person's permanent attendance-marking link into a printable QR
 * code, for `student.id-card` and `staff.id-card`.
 *
 * The link itself (`URL::signedRoute()`, no expiry) is what `QrAttendanceController`
 * and `QrAttendanceMarkingService` already trust — this class only draws it,
 * it does not decide what is safe to encode.
 */
class QrCodeService
{
    /**
     * A data URI (`data:image/png;base64,...`) so the ID card print view can
     * embed the image inline with no extra request — the same reason
     * `Student::image_url` resolves to a servable URL rather than a path.
     */
    public function forStudent(Student $student): string
    {
        return $this->dataUri(URL::signedRoute('attendance.qr.student', $student));
    }

    public function forStaff(StaffProfile $staff): string
    {
        return $this->dataUri(URL::signedRoute('attendance.qr.staff', $staff));
    }

    private function dataUri(string $url): string
    {
        $result = (new Builder(writer: new PngWriter))->build(
            data: $url,
            size: 160,
            margin: 4,
        );

        return $result->getDataUri();
    }
}
