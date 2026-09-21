<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Services\Attendance\QrAttendanceMarkingService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The unauthenticated endpoints a QR code (or a USB barcode scanner typing
 * the same URL into an address bar) hits to mark attendance.
 *
 * There is no login here — a barcode scanner has no session, and the whole
 * point of Method 2 is that it works without one. `Route::signed()` /
 * the `signed` route middleware is what stands in for auth: the URL only
 * works if this application generated it, so nobody can mark someone else's
 * attendance by guessing or sharing a plain `employee_no`/`registration_no`.
 *
 * Both methods deliberately do the same three things in the same order:
 * check the day is a working day, mark (or notice it is already marked),
 * and render a plain confirmation page — no JSON, since a barcode scanner
 * or a phone's camera app just opens the link in a browser tab.
 */
class QrAttendanceController extends Controller
{
    public function __construct(
        private QrAttendanceMarkingService $marking
    ) {}

    /**
     * Marks a student present for today from their ID card's QR code.
     */
    public function markStudent(Request $request, Student $student): View
    {
        try {
            $record = $this->marking->markStudent($student);

            return view('attendance.qr-confirmation', [
                'name' => $student->user->name ?? $student->registration_no,
                'identifier' => $student->registration_no,
                'success' => true,
                'message' => 'Attendance marked for today.',
                'markedAt' => $record->check_in,
            ]);
        } catch (ValidationException $exception) {
            return view('attendance.qr-confirmation', [
                'name' => $student->user->name ?? $student->registration_no,
                'identifier' => $student->registration_no,
                'success' => false,
                'message' => collect($exception->errors())->flatten()->first(),
                'markedAt' => null,
            ]);
        }
    }

    /**
     * Marks a member of staff present for today from their ID card's QR code.
     */
    public function markStaff(Request $request, StaffProfile $staffProfile): View
    {
        try {
            $record = $this->marking->markStaff($staffProfile);

            return view('attendance.qr-confirmation', [
                'name' => $staffProfile->user->name ?? $staffProfile->employee_no,
                'identifier' => $staffProfile->employee_no,
                'success' => true,
                'message' => 'Attendance marked for today.',
                'markedAt' => $record->check_in,
            ]);
        } catch (ValidationException $exception) {
            return view('attendance.qr-confirmation', [
                'name' => $staffProfile->user->name ?? $staffProfile->employee_no,
                'identifier' => $staffProfile->employee_no,
                'success' => false,
                'message' => collect($exception->errors())->flatten()->first(),
                'markedAt' => null,
            ]);
        }
    }
}
