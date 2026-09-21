<?php

/**
 * The unauthenticated student side of QR attendance: a signed URL, printed
 * as a QR code on the student's ID card, that marks them present for today
 * with no login required. `signed` route middleware is the only guard —
 * see `routes/attendance.php`.
 */

use App\Models\AttendanceStudent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->world->policy();
    Carbon::setTestNow('2026-09-21 08:00:00'); // a Monday, a working day
});

afterEach(function () {
    Carbon::setTestNow();
});

it('marks a student present on a validly signed link, with no login', function () {
    [$student] = $this->world->enrol(1);

    $signed = URL::signedRoute('attendance.qr.student', $student);

    $response = $this->get($signed);

    $response->assertOk();
    expect(AttendanceStudent::where('student_id', $student->id)->count())->toBe(1);
});

it('refuses an unsigned request', function () {
    [$student] = $this->world->enrol(1);

    $response = $this->get(route('attendance.qr.student', $student));

    $response->assertForbidden();
});

it('refuses a link tampered to point at a different student', function () {
    [$studentA, $studentB] = $this->world->enrol(2);

    $signed = URL::signedRoute('attendance.qr.student', $studentA);
    $tampered = str_replace((string) $studentA->id, (string) $studentB->id, $signed);

    $response = $this->get($tampered);

    $response->assertForbidden();
});

it('does not duplicate the record when scanned twice the same day', function () {
    [$student] = $this->world->enrol(1);

    $signed = URL::signedRoute('attendance.qr.student', $student);

    $this->get($signed)->assertOk();
    $this->get($signed)->assertOk();

    expect(AttendanceStudent::where('student_id', $student->id)->count())->toBe(1);
});

it('rejects marking on a non-working day', function () {
    [$student] = $this->world->enrol(1);
    $this->world->policy([1, 2, 3, 4, 5]); // Saturday not a working day

    Carbon::setTestNow('2026-09-26 08:00:00'); // a Saturday

    $signed = URL::signedRoute('attendance.qr.student', $student);

    $response = $this->get($signed);

    $response->assertOk();
    $response->assertSee('not a working day');
    expect(AttendanceStudent::where('student_id', $student->id)->count())->toBe(0);
});
