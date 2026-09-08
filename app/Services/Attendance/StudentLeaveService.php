<?php

namespace App\Services\Attendance;

use App\Enums\LeaveStatus;
use App\Models\Student;
use App\Models\StudentLeave;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * A family asking for leave, and the school deciding on it.
 *
 * `student_leaves` already had a status and an approver, but nothing ever
 * created a leave except the office — so those columns described a decision
 * nobody had asked for. A guardian who telephones to say their child will be
 * away for a wedding still relies on somebody remembering to type it in, and
 * when they forget, the child is marked absent and the family is sent a message
 * saying so.
 *
 * **There is no separate guardian portal.** A guardian signs in to the
 * student's portal, so an application is recorded against the child with the
 * user who submitted it noted beside it — which is what tells the office
 * afterwards whether the family asked or the office entered it.
 */
class StudentLeaveService
{
    /**
     * Records an application. It is not leave yet — it is a request.
     */
    public function apply(
        Student $student,
        int $leaveTypeId,
        Carbon $from,
        Carbon $to,
        string $reason,
        ?User $appliedBy = null,
        ?string $attachmentPath = null
    ): StudentLeave {
        return StudentLeave::create([
            'student_id' => $student->id,
            'leave_type_id' => $leaveTypeId,
            'start_date' => $from->toDateString(),
            'end_date' => $to->toDateString(),
            'description' => $reason,
            'status' => LeaveStatus::PENDING,
            'applied_by' => $appliedBy?->id,
            'applied_at' => now(),
            'attachment_path' => $attachmentPath,
        ]);
    }

    /**
     * Approves it, which is what makes the register stop calling it absence.
     *
     * The attendance module already links an approved leave to the day's record
     * by itself, so nothing further has to be remembered here.
     */
    public function approve(StudentLeave $leave, User $decidedBy, ?string $note = null): StudentLeave
    {
        $leave->update([
            'status' => LeaveStatus::APPROVED,
            'approved_by' => $decidedBy->id,
            'decided_at' => now(),
            'decision_note' => $note,
        ]);

        return $leave->fresh();
    }

    /**
     * Refuses it, with the reason recorded.
     *
     * A refusal without a reason is the thing families complain about, and the
     * office cannot answer a month later without one.
     */
    public function reject(StudentLeave $leave, User $decidedBy, string $reason): StudentLeave
    {
        $leave->update([
            'status' => LeaveStatus::REJECTED,
            'approved_by' => $decidedBy->id,
            'decided_at' => now(),
            'decision_note' => $reason,
        ]);

        return $leave->fresh();
    }

    /**
     * Whether these dates already carry a decided leave.
     *
     * A family asking twice for the same days is usually a family that did not
     * see the first answer, so the second request is refused rather than
     * quietly stacked on the first.
     */
    public function overlapsExisting(int $studentId, Carbon $from, Carbon $to, ?int $ignoreId = null): bool
    {
        return StudentLeave::query()
            ->where('student_id', $studentId)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereIn('status', [LeaveStatus::PENDING, LeaveStatus::APPROVED])
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->exists();
    }

    /**
     * Applications a member of staff still has to decide on.
     *
     * @return Collection<int, StudentLeave>
     */
    public function awaitingDecision(?int $classId = null, ?int $sectionId = null)
    {
        return StudentLeave::query()
            ->with(['student.user', 'leaveType'])
            ->pending()
            ->when($classId, fn ($q) => $q->whereHas(
                'student.currentEnrollment',
                fn ($e) => $e->where('class_id', $classId)
                    ->when($sectionId, fn ($s) => $s->where('section_id', $sectionId))
            ))
            ->orderBy('start_date')
            ->get();
    }
}
