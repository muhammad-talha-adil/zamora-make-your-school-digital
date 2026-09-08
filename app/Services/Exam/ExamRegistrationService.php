<?php

namespace App\Services\Exam;

use App\Models\Exam\Exam;
use App\Models\Exam\ExamStudentRegistration;
use App\Models\StudentEnrollmentRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamRegistrationService
{
    /**
     * Generate registrations from student enrollments.
     */
    public function generateFromEnrollments($examId, $classId = null, $sectionId = null, $campusId = null)
    {
        return DB::transaction(function () use ($examId, $classId, $sectionId, $campusId) {
            // Get exam to find session
            $exam = Exam::findOrFail($examId);
            $sessionId = $exam->session_id;

            /*
             * The roll as it stood **when the exam was sat**, not as it stands
             * today. A child who has since moved section is registered where
             * they were, and one who has since left is still registered for the
             * exam they actually sat — which is the whole reason the enrollment
             * periods exist.
             *
             * The previous query asked for `status = 'active'`, and
             * `student_enrollment_records` has no `status` column at all. Every
             * call threw `Unknown column`, so registering a class in bulk had
             * never once worked. The model has carried `active()` for this the
             * whole time.
             */
            $enrollmentQuery = StudentEnrollmentRecord::where('session_id', $sessionId);

            if ($exam->start_date) {
                $enrollmentQuery->overlappingPeriod(
                    $exam->start_date,
                    $exam->end_date ?: $exam->start_date
                );
            } else {
                // An exam with no dates yet: the best available answer is who
                // is on the roll now.
                $enrollmentQuery->active();
            }

            if ($campusId) {
                $enrollmentQuery->where('campus_id', $campusId);
            }
            if ($classId) {
                $enrollmentQuery->where('class_id', $classId);
            }
            if ($sectionId) {
                $enrollmentQuery->where('section_id', $sectionId);
            }

            $enrollments = $enrollmentQuery->get();

            $existingRegistrations = ExamStudentRegistration::where('exam_id', $examId)
                ->pluck('student_id')
                ->toArray();

            $registrations = [];
            $now = now();

            foreach ($enrollments as $enrollment) {
                if (! in_array($enrollment->student_id, $existingRegistrations)) {
                    $registrations[] = [
                        'exam_id' => $examId,
                        'student_id' => $enrollment->student_id,
                        'campus_id' => $enrollment->campus_id,
                        'class_id' => $enrollment->class_id,
                        'section_id' => $enrollment->section_id,
                        'enrollment_id' => $enrollment->id,
                        'status' => 'registered',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (! empty($registrations)) {
                ExamStudentRegistration::insert($registrations);
            }

            return count($registrations);
        });
    }

    /**
     * The registration a child holds for an exam, created if marks arrive
     * before anybody pressed "register class".
     *
     * `exam_student_registrations` was populated by the bulk routine and then
     * never consulted: marks could be entered for a child who was never
     * registered, and a header appeared for them on the spot. Two tables then
     * disagreed about who sat the exam, and "how many are still to be marked"
     * could not be answered.
     *
     * The rule is that **registration is implicit and recorded**, not implicit
     * and invisible. A teacher marking a child who was missed off the list
     * should not be stopped — that is normal here — but the register is written
     * so the two tables agree.
     *
     * Two things are still refused: a child with no enrollment covering the
     * exam at all, and one whose registration was deliberately withdrawn.
     *
     * The enrollment is read from the database rather than taken from the
     * request. The marking grid posts an `enrollment_id` of its own, and
     * trusting it would let the caller name any enrollment they liked and walk
     * straight past the roll check.
     *
     * @throws ValidationException
     */
    public function registrationFor(Exam $exam, int $studentId): ExamStudentRegistration
    {
        $existing = ExamStudentRegistration::where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->first();

        if ($existing) {
            if ($existing->status === 'withdrawn') {
                throw ValidationException::withMessages([
                    'student_id' => 'This child was withdrawn from the exam. Register them again before entering marks.',
                ]);
            }

            return $existing;
        }

        $enrollment = $this->enrollmentDuring($exam, $studentId);

        if (! $enrollment) {
            throw ValidationException::withMessages([
                'student_id' => 'This child was not on the roll when this exam was sat, so marks cannot be entered for them.',
            ]);
        }

        return ExamStudentRegistration::create([
            'exam_id' => $exam->id,
            'student_id' => $studentId,
            'campus_id' => $enrollment->campus_id,
            'class_id' => $enrollment->class_id,
            'section_id' => $enrollment->section_id,
            'enrollment_id' => $enrollment->id,
            'status' => 'registered',
        ]);
    }

    /**
     * The enrollment a child held while the exam was on.
     *
     * The same reading of the roll the bulk routine uses, for one child.
     */
    public function enrollmentDuring(Exam $exam, int $studentId): ?StudentEnrollmentRecord
    {
        $query = StudentEnrollmentRecord::where('student_id', $studentId)
            ->where('session_id', $exam->session_id);

        if ($exam->start_date) {
            $query->overlappingPeriod($exam->start_date, $exam->end_date ?: $exam->start_date);
        } else {
            $query->active();
        }

        return $query->latest('admission_date')->first();
    }

    public function registerStudent(array $data)
    {
        return DB::transaction(function () use ($data) {
            return ExamStudentRegistration::create($data);
        });
    }

    public function withdraw(ExamStudentRegistration $registration)
    {
        return DB::transaction(function () use ($registration) {
            $registration->update(['status' => 'withdrawn']);

            return $registration->fresh();
        });
    }
}
