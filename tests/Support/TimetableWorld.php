<?php

namespace Tests\Support;

use App\Models\StaffProfile;
use App\Models\Subject;
use App\Models\TeacherClassAssignment;
use App\Models\Timetable\TimetableEntry;
use App\Models\Timetable\TimetablePeriod;
use App\Models\User;

/**
 * A school with a timetable skeleton: two lesson periods and a break on the
 * shared campus/session, a subject, and helpers to place lessons and make
 * teachers. Built on `PortalWorld` so student/guardian logins and the real
 * seeded roles are available.
 */
class TimetableWorld
{
    public PortalWorld $portal;

    public AdmissionWorld $school;

    public TimetablePeriod $first;

    public TimetablePeriod $second;

    public TimetablePeriod $break;

    public Subject $maths;

    public function __construct()
    {
        $this->portal = PortalWorld::make();
        $this->school = $this->portal->fee->school;

        $this->first = $this->period('Period 1', '08:00', '08:40', 1);
        $this->second = $this->period('Period 2', '08:40', '09:20', 2);
        $this->break = $this->period('Break', '09:20', '09:40', 3, true);

        $this->maths = Subject::create(['name' => 'Maths', 'short_name' => 'MTH', 'is_active' => true]);
    }

    public static function make(): self
    {
        return new self;
    }

    public function period(string $name, string $start, string $end, int $order, bool $isBreak = false): TimetablePeriod
    {
        return TimetablePeriod::create([
            'campus_id' => $this->school->campus->id,
            'session_id' => $this->school->session->id,
            'name' => $name,
            'start_time' => $start,
            'end_time' => $end,
            'sort_order' => $order,
            'is_break' => $isBreak,
            'is_active' => true,
        ]);
    }

    /**
     * A teacher login with a staff record at the shared campus, optionally
     * given a section to teach.
     *
     * @return array{0: User, 1: StaffProfile}
     */
    public function teacher(bool $assignSection = true, bool $otherCampus = false): array
    {
        $user = $this->school->userWithRole('teacher', 'teacher.'.uniqid().'@school.test');

        $profile = StaffProfile::create([
            'user_id' => $user->id,
            'employee_no' => 'EMP-TT-'.$user->id,
            'campus_id' => $otherCampus ? $this->school->otherCampus->id : $this->school->campus->id,
            'employment_type' => 'permanent',
            'hire_date' => '2026-04-01',
            'is_active' => true,
        ]);

        if ($assignSection) {
            TeacherClassAssignment::create([
                'staff_profile_id' => $profile->id,
                'session_id' => $this->school->session->id,
                'class_id' => $this->school->class->id,
                'section_id' => $this->school->section->id,
                'is_active' => true,
            ]);
        }

        return [$user->fresh(), $profile];
    }

    /**
     * Payload for placing a lesson into the shared section.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function payload(StaffProfile $teacher, array $overrides = []): array
    {
        return array_merge([
            'class_id' => $this->school->class->id,
            'section_id' => $this->school->section->id,
            'subject_id' => $this->maths->id,
            'staff_profile_id' => $teacher->id,
            'period_id' => $this->first->id,
            'weekday' => 1,
            'room' => null,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public function entry(StaffProfile $teacher, array $overrides = []): TimetableEntry
    {
        $data = $this->payload($teacher, $overrides);

        return TimetableEntry::create($data + [
            'campus_id' => $this->school->campus->id,
            'session_id' => $this->school->session->id,
        ]);
    }

    public function admin(): User
    {
        return $this->school->userWithRole('campus_admin', 'admin.'.uniqid().'@school.test');
    }
}
