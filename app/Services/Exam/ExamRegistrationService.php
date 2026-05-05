<?php

namespace App\Services\Exam;

use App\Models\Exam\Exam;
use App\Models\Exam\ExamStudentRegistration;
use App\Models\StudentEnrollmentRecord;
use Illuminate\Support\Facades\DB;

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

            $enrollmentQuery = StudentEnrollmentRecord::where('session_id', $sessionId)
                ->where('status', 'active');

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
