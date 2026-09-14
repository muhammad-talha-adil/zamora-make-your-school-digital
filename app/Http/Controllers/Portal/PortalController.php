<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Concerns\ResolvesOwnStudent;
use App\Http\Controllers\Controller;
use App\Models\AttendanceStudent;
use App\Models\Exam\ExamResultHeader;
use App\Models\Fee\FeeVoucher;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The portal landing page.
 *
 * A lean summary, not a full dashboard: the child's name, an outstanding fee
 * balance if there is one, the most recent exam result if there is one, and
 * today's attendance status if it has been marked. Anything more belongs on
 * the dedicated `/portal/fees`, `/portal/exams` and `/portal/attendance`
 * screens.
 */
class PortalController extends Controller
{
    use ResolvesOwnStudent;

    public function index(Request $request): Response
    {
        $user = $request->user();
        $students = $this->ownStudents($user);
        $student = $this->resolveOwnStudent($user, $request->integer('student_id') ?: null);

        $outstandingVoucher = FeeVoucher::where('student_id', $student->id)
            ->where('balance_amount', '>', 0)
            ->orderByDesc('due_date')
            ->first();

        $latestResult = ExamResultHeader::where('student_id', $student->id)
            ->whereIn('status', [ExamResultHeader::STATUS_PUBLISHED, ExamResultHeader::STATUS_LOCKED])
            ->with(['exam.examType', 'overallGradeItem'])
            ->latest('id')
            ->first();

        $todayAttendance = AttendanceStudent::where('student_id', $student->id)
            ->whereHas('attendance', fn ($q) => $q->whereDate('attendance_date', now()->toDateString()))
            ->with('attendanceStatus:id,name,code')
            ->first();

        return Inertia::render('Portal/Index', [
            'student' => ['id' => $student->id, 'name' => $student->user?->name],
            'students' => $students->map(fn ($s) => ['id' => $s->id, 'name' => $s->user?->name])->values(),
            'outstandingVoucher' => $outstandingVoucher ? [
                'id' => $outstandingVoucher->id,
                'voucher_no' => $outstandingVoucher->voucher_no,
                'balance_amount' => (float) $outstandingVoucher->balance_amount,
                'due_date' => $outstandingVoucher->due_date?->format('Y-m-d'),
            ] : null,
            'latestResult' => $latestResult ? [
                'id' => $latestResult->id,
                'exam' => $latestResult->exam?->name,
                'status' => $latestResult->status,
                'result_status' => $latestResult->result_status,
                'grade' => $latestResult->overallGradeItem?->grade_letter,
                'percentage' => $latestResult->overall_percentage_cache,
            ] : null,
            'todayAttendance' => $todayAttendance ? [
                'status' => $todayAttendance->attendanceStatus?->name,
                'code' => $todayAttendance->attendanceStatus?->code,
            ] : null,
        ]);
    }
}
