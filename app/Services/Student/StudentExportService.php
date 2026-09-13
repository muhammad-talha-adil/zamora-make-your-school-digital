<?php

namespace App\Services\Student;

use App\Models\Student;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Getting the roll out of the system.
 *
 * `export()` used to be this, in full:
 *
 * ```php
 * return response()->json([
 *     'success' => true,
 *     'message' => 'Export started. You will be notified when ready.',
 * ]);
 * ```
 *
 * Nothing was exported, nothing was queued, and nobody was ever notified. The
 * route existed, the permission was seeded, the button was on the screen, and
 * the user was told it had worked.
 *
 * CSV, streamed. No dependency was added for it: a school opens this in Excel
 * either way, and a file it can also open in Notepad is a file it can fix when
 * something is wrong with it.
 */
class StudentExportService
{
    /**
     * The columns, in the order the file carries them.
     *
     * The same header the importer reads, so a school can export a class,
     * correct it in Excel, and put it back.
     *
     * @var array<int, string>
     */
    public const COLUMNS = [
        'admission_no',
        'student_code',
        'registration_no',
        'name',
        'dob',
        'gender',
        'b_form',
        'status',
        'admission_date',
        'campus',
        'class',
        'section',
        'session',
        'monthly_fee',
        'annual_fee',
        'father_name',
        'father_phone',
        'father_cnic',
    ];

    /**
     * Streams the roll as a CSV.
     *
     * Chunked, because a school with four thousand children on one campus
     * would otherwise hold all four thousand in memory to build a file it is
     * only going to write out row by row anyway.
     *
     * @param  array<string, mixed>  $filters
     */
    public function stream(?User $viewer, array $filters = []): StreamedResponse
    {
        $filename = 'students-'.now()->format('Y-m-d-Hi').'.csv';

        return response()->streamDownload(function () use ($viewer, $filters) {
            $handle = fopen('php://output', 'w');

            // Excel reads a CSV as the system codepage unless the file says
            // otherwise, and a Pakistani school's names are full of characters
            // that then arrive as mojibake.
            fwrite($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, self::COLUMNS);

            $this->query($viewer, $filters)->chunk(500, function ($students) use ($handle) {
                foreach ($students as $student) {
                    fputcsv($handle, $this->row($student));
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * How many rows an export would carry, for the screen that asks first.
     *
     * @param  array<string, mixed>  $filters
     */
    public function countFor(?User $viewer, array $filters = []): int
    {
        return $this->query($viewer, $filters)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function query(?User $viewer, array $filters)
    {
        return Student::query()
            // What comes out is what this person may see. The ability alone
            // used to hand over the whole school.
            ->visibleTo($viewer)
            ->with([
                'user:id,name',
                'gender:id,name',
                'studentStatus:id,name',
                'currentEnrollment.campus',
                'currentEnrollment.class',
                'currentEnrollment.section',
                'currentEnrollment.session',
                'studentGuardians.guardian.user',
            ])
            ->when(
                ! empty($filters['class_id']),
                fn ($q) => $q->whereHas('enrollmentRecords', fn ($e) => $e
                    ->whereNull('leave_date')
                    ->where('class_id', $filters['class_id']))
            )
            ->when(
                ! empty($filters['section_id']),
                fn ($q) => $q->whereHas('enrollmentRecords', fn ($e) => $e
                    ->whereNull('leave_date')
                    ->where('section_id', $filters['section_id']))
            )
            ->when(
                ! empty($filters['campus_id']),
                fn ($q) => $q->whereHas('enrollmentRecords', fn ($e) => $e
                    ->whereNull('leave_date')
                    ->where('campus_id', $filters['campus_id']))
            )
            ->orderBy('id');
    }

    /**
     * @return array<int, string|null>
     */
    private function row(Student $student): array
    {
        $enrollment = $student->currentEnrollment;

        $guardian = $student->studentGuardians
            ->sortByDesc(fn ($link) => (bool) $link->is_primary)
            ->first()?->guardian;

        return [
            $student->admission_no,
            $student->student_code,
            $student->registration_no,
            $student->user?->name,
            $student->dob?->toDateString(),
            $student->gender?->name,
            $student->b_form,
            $student->studentStatus?->name,
            $student->admission_date?->toDateString(),
            $enrollment?->campus?->name,
            $enrollment?->class?->name,
            $enrollment?->section?->name,
            $enrollment?->session?->name,
            $enrollment?->monthly_fee,
            $enrollment?->annual_fee,
            $guardian?->user?->name,
            $guardian?->phone,
            $guardian?->cnic,
        ];
    }
}
