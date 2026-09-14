<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Concerns\ResolvesOwnStudent;
use App\Http\Controllers\Controller;
use App\Models\AttendanceStudent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A family's own attendance history.
 *
 * `attendance.view.own` was seeded since the beginning and nothing consumed
 * it — no route or controller existed for a student/guardian to read their
 * own record. This is that endpoint: a plain, paginated, day-by-day list,
 * scoped to the caller's own resolved student.
 */
class PortalAttendanceController extends Controller
{
    use ResolvesOwnStudent;

    public function index(Request $request): Response
    {
        $user = $request->user();
        $students = $this->ownStudents($user);
        $student = $this->resolveOwnStudent($user, $request->integer('student_id') ?: null);

        $records = AttendanceStudent::where('student_id', $student->id)
            ->with(['attendance:id,attendance_date', 'attendanceStatus:id,name,code'])
            ->whereHas('attendance')
            ->join('attendances', 'attendances.id', '=', 'attendance_students.attendance_id')
            ->orderByDesc('attendances.attendance_date')
            ->select('attendance_students.*')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Portal/Attendance/Index', [
            'student' => ['id' => $student->id, 'name' => $student->user?->name],
            'students' => $students->map(fn ($s) => ['id' => $s->id, 'name' => $s->user?->name])->values(),
            'records' => $records,
        ]);
    }
}
