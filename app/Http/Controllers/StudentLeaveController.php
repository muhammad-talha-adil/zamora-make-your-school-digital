<?php

namespace App\Http\Controllers;

use App\Http\Requests\Attendance\ApplyStudentLeaveRequest;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\StudentLeave;
use App\Services\Attendance\StudentLeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Leave applications, from the family and from the office.
 *
 * There is no separate guardian portal — a guardian signs in to the student's
 * portal — so the same endpoint serves both, and who submitted it is recorded
 * rather than inferred.
 */
class StudentLeaveController extends Controller
{
    public function __construct(private StudentLeaveService $leaves) {}

    /**
     * A child's own leave history.
     */
    public function index(Request $request, Student $student): JsonResponse
    {
        $this->assertMayActFor($request, $student);

        return response()->json([
            'leaves' => $student->studentLeaves()
                ->with(['leaveType', 'approver'])
                ->orderByDesc('start_date')
                ->get(),
        ]);
    }

    /**
     * Applies for leave. This is a request, not yet leave.
     */
    public function store(ApplyStudentLeaveRequest $request, Student $student): JsonResponse
    {
        $this->assertMayActFor($request, $student);

        $from = Carbon::parse($request->validated('start_date'));
        $to = Carbon::parse($request->validated('end_date'));

        // A family asking twice for the same days is usually a family that did
        // not see the first answer.
        if ($this->leaves->overlapsExisting($student->id, $from, $to)) {
            return response()->json([
                'message' => 'An application already covers some of those days.',
            ], 422);
        }

        $leave = $this->leaves->apply(
            $student,
            (int) $request->validated('leave_type_id'),
            $from,
            $to,
            $request->validated('description'),
            $request->user(),
            $request->validated('attachment_path')
        );

        return response()->json(['leave' => $leave->load('leaveType')], 201);
    }

    /**
     * Applications the school still has to decide on.
     */
    public function pending(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Attendance::class);

        return response()->json([
            'leaves' => $this->leaves->awaitingDecision(
                $request->integer('class_id') ?: null,
                $request->integer('section_id') ?: null
            ),
        ]);
    }

    /**
     * Approves an application, which is what stops the register calling those
     * days absence.
     */
    public function approve(Request $request, StudentLeave $leave): JsonResponse
    {
        $this->authorize('update', $this->registerContextFor($leave));

        return response()->json([
            'leave' => $this->leaves->approve($leave, $request->user(), $request->input('note')),
        ]);
    }

    /**
     * Refuses an application. A reason is required, because the office cannot
     * answer the family a month later without one.
     */
    public function reject(Request $request, StudentLeave $leave): JsonResponse
    {
        $this->authorize('update', $this->registerContextFor($leave));

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        return response()->json([
            'leave' => $this->leaves->reject($leave, $request->user(), $validated['reason']),
        ]);
    }

    /**
     * A stand-in register for the child's class, so the attendance policy
     * decides who may act on the leave.
     *
     * Whoever may change that class's register may decide its leave: the two
     * are the same responsibility, and answering it in one place keeps them
     * from drifting apart.
     */
    private function registerContextFor(StudentLeave $leave): Attendance
    {
        return $this->registerContextForStudent($leave->student);
    }

    /**
     * The same, from the child.
     */
    private function registerContextForStudent(?Student $student): Attendance
    {
        $enrollment = $student?->currentEnrollment;

        return new Attendance([
            'campus_id' => $enrollment?->campus_id,
            'session_id' => $enrollment?->session_id,
            'class_id' => $enrollment?->class_id,
            'section_id' => $enrollment?->section_id,
            'is_locked' => false,
        ]);
    }

    /**
     * Whether this user may act for this child.
     *
     * The child themselves, a guardian signed in to the child's portal, or a
     * member of staff who may see that class's register.
     */
    private function assertMayActFor(Request $request, Student $student): void
    {
        $user = $request->user();

        if ($user && (int) $student->user_id === (int) $user->id) {
            return;
        }

        if ($user && $student->guardians()->whereHas('user', fn ($q) => $q->whereKey($user->id))->exists()) {
            return;
        }

        $this->authorize('view', $this->registerContextForStudent($student));
    }
}
