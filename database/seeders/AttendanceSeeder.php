<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceStatus;
use App\Models\AttendanceStudent;
use App\Models\Student;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates sample attendance records for testing purposes.
     * Only creates records for students with active enrollments.
     */
    public function run(): void
    {
        // Get attendance statuses
        $presentStatus = AttendanceStatus::where('code', 'P')->first();
        $absentStatus = AttendanceStatus::where('code', 'A')->first();
        $leaveStatus = AttendanceStatus::where('code', 'L')->first();
        $lateStatus = AttendanceStatus::where('code', 'LT')->first();

        if (! $presentStatus || ! $absentStatus) {
            return; // Skip if statuses not created yet
        }

        // Get students with active enrollments (limit to 20 for demo)
        $students = Student::whereHas('currentEnrollment')
            ->with('currentEnrollment')
            ->limit(20)
            ->get();

        if ($students->isEmpty()) {
            return; // No students with active enrollment
        }

        // Create attendance for the last 5 days (excluding weekends)
        $enrollment = $students[0]->currentEnrollment;
        $startDate = now()->subDays(5)->startOfDay();

        for ($i = 0; $i < 5; $i++) {
            $date = $startDate->copy()->addDays($i);

            // Skip weekends
            if ($date->isWeekend()) {
                continue;
            }

            // Check if attendance already exists
            $existingAttendance = Attendance::where('attendance_date', $date)
                ->where('class_id', $enrollment->class_id)
                ->where('section_id', $enrollment->section_id)
                ->exists();

            if ($existingAttendance) {
                continue;
            }

            // Create attendance record
            $attendance = Attendance::create([
                'attendance_date' => $date,
                'campus_id' => $enrollment->campus_id,
                'session_id' => $enrollment->session_id,
                'class_id' => $enrollment->class_id,
                'section_id' => $enrollment->section_id,
                'taken_by' => 1, // Assuming admin user
                'is_locked' => false,
            ]);

            // Create attendance for each student with varied statuses
            $students->each(function ($student, $index) use ($attendance, $presentStatus, $absentStatus, $leaveStatus, $lateStatus) {
                // Create varied attendance patterns
                $statuses = [$presentStatus, $absentStatus, $lateStatus];
                $status = $statuses[$index % 3];

                // 10% chance of leave (would require approved leave in real scenario)
                if ($index % 10 === 0) {
                    $status = $leaveStatus;
                }

                AttendanceStudent::create([
                    'attendance_id' => $attendance->id,
                    'student_id' => $student->id,
                    'attendance_status_id' => $status->id,
                    'student_leave_id' => null,
                    'check_in' => $status->code === 'LT' ? '08:30:00' : '08:00:00',
                    'check_out' => '14:00:00',
                    'remarks' => null,
                ]);
            });
        }
    }
}
