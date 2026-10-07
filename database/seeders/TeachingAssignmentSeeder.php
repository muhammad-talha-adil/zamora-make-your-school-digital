<?php

namespace Database\Seeders;

use App\Models\Session;
use App\Models\Staff\StaffSubject;
use App\Models\StaffProfile;
use App\Models\Subject;
use App\Models\TeacherClassAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Demo data for the Teaching screen: who takes Class Five-A this session, and
 * who could be asked to cover a subject at short notice.
 *
 * `teacher_class_assignments` and `staff_subjects` are written to by the
 * Teaching screen itself but nothing has ever seeded either table, so the
 * demo database shows "no assignments" and "nobody can cover" everywhere.
 *
 * Dev/demo data only — not part of the default production seed list.
 */
class TeachingAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $session = Session::where('name', '2025-2026')->first();
        $classFive = \App\Models\SchoolClass::where('name', 'Five')->first();
        $sectionA = $classFive
            ? \App\Models\Section::where('class_id', $classFive->id)->where('name', 'A')->first()
            : null;

        if (! $session || ! $classFive || ! $sectionA) {
            $this->command?->warn('TeachingAssignmentSeeder skipped: session 2025-2026, Class Five, or Section A missing.');

            return;
        }

        $subjects = Subject::whereIn('name', ['Mathematics', 'English', 'Science', 'Social Studies', 'Biology'])
            ->get()->keyBy('name');

        $teachers = StaffProfile::query()
            ->whereHas('user')
            ->where('is_active', true)
            ->whereHas('designation', fn ($q) => $q->where('name', 'Teacher'))
            ->with('user')
            ->orderBy('id')
            ->get();

        if ($teachers->isEmpty()) {
            $this->command?->warn('TeachingAssignmentSeeder skipped: no active teaching staff found.');

            return;
        }

        $this->seedClassFiveAAssignments($session, $classFive, $sectionA, $subjects, $teachers);
        $this->seedBiologyCoverage($subjects, $teachers);

        $this->command?->info('Teaching assignments and subject coverage seeded successfully.');
    }

    /**
     * @param  Collection<string, Subject>  $subjects
     * @param  Collection<int, StaffProfile>  $teachers
     */
    private function seedClassFiveAAssignments(
        Session $session,
        \App\Models\SchoolClass $classFive,
        \App\Models\Section $sectionA,
        Collection $subjects,
        Collection $teachers
    ): void {
        $plan = [
            ['subject' => 'Mathematics', 'is_class_teacher' => true, 'periods_per_week' => 6],
            ['subject' => 'English', 'is_class_teacher' => false, 'periods_per_week' => 5],
            ['subject' => 'Science', 'is_class_teacher' => false, 'periods_per_week' => 5],
            ['subject' => 'Social Studies', 'is_class_teacher' => false, 'periods_per_week' => 4],
        ];

        foreach ($plan as $index => $row) {
            $teacher = $teachers[$index % $teachers->count()];
            $subject = $subjects[$row['subject']] ?? null;

            if (! $subject) {
                continue;
            }

            TeacherClassAssignment::updateOrCreate(
                [
                    'staff_profile_id' => $teacher->id,
                    'session_id' => $session->id,
                    'class_id' => $classFive->id,
                    'section_id' => $sectionA->id,
                    'subject_id' => $subject->id,
                ],
                [
                    'is_class_teacher' => $row['is_class_teacher'],
                    'periods_per_week' => $row['periods_per_week'],
                    'is_active' => true,
                ]
            );

            StaffSubject::firstOrCreate(
                ['staff_profile_id' => $teacher->id, 'subject_id' => $subject->id],
                ['is_primary' => $row['is_class_teacher']]
            );
        }
    }

    /**
     * Marks a couple of teachers able to cover Biology, for the "who can
     * cover a subject" lookup.
     *
     * @param  Collection<string, Subject>  $subjects
     * @param  Collection<int, StaffProfile>  $teachers
     */
    private function seedBiologyCoverage(Collection $subjects, Collection $teachers): void
    {
        $biology = $subjects['Biology'] ?? null;

        if (! $biology) {
            return;
        }

        foreach ($teachers->take(2) as $teacher) {
            StaffSubject::firstOrCreate(
                ['staff_profile_id' => $teacher->id, 'subject_id' => $biology->id],
                ['is_primary' => false]
            );
        }
    }
}
