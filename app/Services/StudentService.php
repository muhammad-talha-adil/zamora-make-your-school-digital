<?php

namespace App\Services;

use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Repositories\StudentRepository;
use App\Services\Student\StudentEnrollmentService;
use App\Services\Student\StudentExportService;
use App\Services\Student\StudentImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        protected StudentRepository $repository,
        protected StudentEnrollmentService $enrollments,
        protected StudentExportService $exports,
        protected StudentImportService $imports
    ) {}

    /**
     * Get students for index page with filters.
     */
    public function getIndexData(Request $request): array
    {
        $filters = $request->only(['campus_id', 'class_id', 'section_id', 'gender_id', 'status', 'search']);
        $students = $this->repository->getPaginatedWithFilters($filters, 10);

        // Add serial numbers and current enrollment data
        $this->processStudentsForDisplay($students);

        $lookupData = $this->repository->getLookupData();

        return [
            'tableStudents' => $students,
            'campuses' => $lookupData['campuses'],
            'classes' => $lookupData['classes'],
            'sections' => $lookupData['sections'],
            'genders' => $lookupData['genders'],
            'statuses' => $lookupData['studentStatuses'],
            'filters' => $filters,
        ];
    }

    /**
     * Process students for display (add serial numbers, current enrollment).
     */
    private function processStudentsForDisplay($students): void
    {
        $currentPage = $students->currentPage();
        $perPage = $students->perPage();

        $students->each(function ($student, $index) use ($currentPage, $perPage) {
            $student->serial = ($currentPage - 1) * $perPage + $index + 1;

            // Get current enrollment
            $enrollment = $student->enrollmentRecords
                ->sortByDesc('admission_date')
                ->first(fn ($r) => $r->leave_date === null);

            if (! $enrollment) {
                $enrollment = $student->enrollmentRecords->sortByDesc('admission_date')->first();
            }

            if ($enrollment) {
                $student->current_enrollment = [
                    'campus' => $enrollment->campus ? ['name' => $enrollment->campus->name] : null,
                    'class' => $enrollment->class ? ['name' => $enrollment->class->name] : null,
                    'section' => $enrollment->section ? ['name' => $enrollment->section->name] : null,
                    'session' => $enrollment->session ? ['name' => $enrollment->session->name] : null,
                ];
            } else {
                $student->current_enrollment = null;
            }

            // Transform guardians to plain array format
            $student->student_guardians = $student->studentGuardians->map(function ($studentGuardian) {
                return [
                    'id' => $studentGuardian->id,
                    'pivot' => [
                        'id' => $studentGuardian->id,
                        'relation_id' => $studentGuardian->relation_id,
                        'is_primary' => $studentGuardian->is_primary,
                    ],
                    'guardian' => [
                        'user' => $studentGuardian->guardian?->user ? ['name' => $studentGuardian->guardian->user->name] : null,
                        'phone' => $studentGuardian->guardian?->phone,
                    ],
                    'relation' => $studentGuardian->relation ? ['name' => $studentGuardian->relation->name] : null,
                ];
            })->toArray();
        });
    }

    /**
     * Get data for create page.
     */
    public function getCreateData(): array
    {
        $lookupData = $this->repository->getLookupData();

        return [
            'campuses' => $lookupData['campuses'],
            'sessions' => $lookupData['sessions'],
            'classes' => $lookupData['classes'],
            'sections' => $lookupData['sections'],
            'genders' => $lookupData['genders'],
            'relations' => $lookupData['relations'],
            'studentStatuses' => $lookupData['studentStatuses'],
            'admissionNo' => $this->repository->generateAdmissionNumber(),
        ];
    }

    /**
     * Get data for edit page with all student data exactly matching Edit.vue expectations.
     */
    public function getEditData(Student $student): array
    {
        // Load all required relationships for the edit form
        // Using 'studentGuardians' (HasMany) which has guardian and relation relationships
        $student->load([
            'user',
            'studentGuardians' => function ($query) {
                $query->with(['guardian.user', 'relation']);
            },
            'enrollmentRecords' => function ($query) {
                $query->with(['campus', 'class', 'section', 'session', 'studentStatus', 'discounts'])
                    ->orderBy('admission_date', 'desc');
            },
        ]);

        // Get the current active enrollment or most recent enrollment
        $currentEnrollment = $student->enrollmentRecords
            ->sortByDesc('admission_date')
            ->first(fn ($r) => $r->leave_date === null);

        if (! $currentEnrollment) {
            $currentEnrollment = $student->enrollmentRecords->sortByDesc('admission_date')->first();
        }

        $lookupData = $this->repository->getLookupData();

        // Get ALL sections for the class dropdown filtering in Vue
        // (Edit.vue filters sections by class_id on the frontend when class changes)
        $sections = Section::orderBy('name')->get(['id', 'name', 'class_id']);

        // Build the student object exactly as Edit.vue expects
        $studentData = [
            'id' => $student->id,
            // System-issued identifiers, shown read-only on the edit form.
            'admission_no' => $student->admission_no,
            'registration_no' => $student->registration_no,
            'student_code' => $student->student_code,
            'user' => [
                'name' => $student->user?->name,
                'email' => $student->user?->email,
            ],
            'dob' => $student->dob->format('Y-m-d'),
            'gender_id' => $student->gender_id,
            'b_form' => $student->b_form,
            'description' => $student->description,
            'image' => $student->image,
        ];

        // Add enrollment data directly to student object for Edit.vue
        if ($currentEnrollment) {
            $studentData['campus_id'] = $currentEnrollment->campus_id;
            $studentData['session_id'] = $currentEnrollment->session_id;
            $studentData['class_id'] = $currentEnrollment->class_id;
            $studentData['section_id'] = $currentEnrollment->section_id;
            $studentData['student_status_id'] = $currentEnrollment->student_status_id;
            $studentData['admission_date'] = $currentEnrollment->admission_date->format('Y-m-d');
            $studentData['current_enrollment'] = [
                'monthly_fee' => $currentEnrollment->monthly_fee,
                'annual_fee' => $currentEnrollment->annual_fee,
                // NEW: Fee structure integration fields
                'fee_structure_id' => $currentEnrollment->fee_structure_id,
                'fee_mode' => $currentEnrollment->fee_mode,
                'discounts' => $currentEnrollment->discounts->map(fn ($discount) => [
                    'fee_head_id' => $discount->fee_head_id,
                    'discount_type_id' => $discount->discount_type_id,
                    'value' => (float) $discount->value,
                    'value_type' => $discount->value_type,
                ])->values()->toArray(),
                'custom_fee_entries' => $currentEnrollment->custom_fee_entries,
                'manual_discount_percentage' => $currentEnrollment->manual_discount_percentage,
                'manual_discount_reason' => $currentEnrollment->manual_discount_reason,
            ];
        } else {
            $studentData['campus_id'] = null;
            $studentData['session_id'] = null;
            $studentData['class_id'] = null;
            $studentData['section_id'] = null;
            $studentData['student_status_id'] = $student->student_status_id;
            $studentData['admission_date'] = null;
            $studentData['current_enrollment'] = [
                'monthly_fee' => 0,
                'annual_fee' => 0,
            ];
        }

        // Build guardians array exactly as Edit.vue expects (using studentGuardians HasMany)
        $guardians = [];
        foreach ($student->studentGuardians as $studentGuardian) {
            $guardians[] = [
                'id' => $studentGuardian->guardian->id ?? 0,
                'name' => $studentGuardian->guardian?->user?->name,
                'phone' => $studentGuardian->guardian?->phone,
                'email' => $studentGuardian->guardian?->user?->email,
                'cnic' => $studentGuardian->guardian?->cnic,
                'occupation' => $studentGuardian->guardian?->occupation,
                'address' => $studentGuardian->guardian?->address,
                'pivot' => [
                    'relation_id' => $studentGuardian->relation_id,
                    'is_primary' => $studentGuardian->is_primary,
                ],
                'relation' => [
                    'name' => $studentGuardian->relation?->name,
                ],
            ];
        }
        $studentData['guardians'] = $guardians;

        return [
            'student' => $studentData,
            'campuses' => $lookupData['campuses'],
            'sessions' => $lookupData['sessions'],
            'classes' => $lookupData['classes'],
            'sections' => $sections,
            'genders' => $lookupData['genders'],
            'relations' => $lookupData['relations'],
            'studentStatuses' => $lookupData['studentStatuses'],
        ];
    }

    /**
     * Get data for show page.
     */
    public function getShowData(Student $student): array
    {
        $student->load([
            'user',
            'gender',
            'studentStatus',
            'studentGuardians.guardian.user',
            'studentGuardians.relation',
            'enrollmentRecords.studentStatus',
            'enrollmentRecords.session',
            'enrollmentRecords.class',
            'enrollmentRecords.section',
            'enrollmentRecords.campus',
            'enrollmentRecords.previousEnrollment',
        ]);

        return [
            'student' => $student,
        ];
    }

    /**
     * Get data for print admission form.
     */
    public function getPrintData(Student $student): array
    {
        $student->load([
            'user',
            'gender',
            'studentStatus',
            'studentGuardians.guardian.user',
            'studentGuardians.relation',
            'enrollmentRecords.studentStatus',
            'enrollmentRecords.session',
            'enrollmentRecords.class',
            'enrollmentRecords.section',
            'enrollmentRecords.campus',
            'enrollmentRecords.feeStructure.items.feeHead',
        ]);

        return [
            'student' => $student,
            'school' => School::first(),
        ];
    }

    /**
     * Get students for dropdown (API).
     */
    public function getDropdownData(Request $request): JsonResponse
    {
        $filters = $request->only(['campus_id', 'class_id']);
        $students = $this->repository->getForDropdown($filters);

        return response()->json($students);
    }

    /**
     * Get sections by class (API).
     */
    public function getSectionsByClass(int $classId): JsonResponse
    {
        $sections = $this->repository->getSectionsByClass($classId);

        return response()->json($sections);
    }

    /**
     * Get guardian by phone (API).
     */
    public function getGuardianByPhone(Request $request): JsonResponse
    {
        $phone = $request->phone;

        if (empty($phone)) {
            return response()->json(['found' => false]);
        }

        $guardian = $this->repository->getGuardianByPhone($phone);

        if ($guardian) {
            return response()->json([
                'found' => true,
                'guardian' => [
                    'id' => $guardian->id,
                    'name' => $guardian->user?->name,
                    'email' => $guardian->user?->email,
                    'phone' => $guardian->phone,
                    'cnic' => $guardian->cnic,
                    'occupation' => $guardian->occupation,
                    'address' => $guardian->address,
                    'students_count' => $guardian->students->count(),
                ],
            ]);
        }

        return response()->json(['found' => false]);
    }

    /**
     * Create a new student.
     */
    public function create(array $data): Student
    {
        return $this->repository->createWithRelationships($data);
    }

    /**
     * Update a student.
     */
    public function update(Student $student, array $data): Student
    {
        return $this->repository->updateWithRelationships($student, $data);
    }

    /**
     * Change student status.
     */
    public function changeStatus(Student $student, array $data): Student
    {
        return $this->repository->changeStatus($student, $data);
    }

    /**
     * Re-admit a student.
     */
    public function readmit(Student $student, array $data): Student
    {
        return $this->repository->readmit($student, $data);
    }

    /**
     * Delete a student (soft delete).
     */
    public function delete(Student $student): bool
    {
        /*
         * Deleting a child has to take them off the roll.
         *
         * It used to soft-delete the `students` row and leave the enrolment
         * period open, so the child vanished from the student list and stayed
         * on the class roll — still billed by the fee run, still expected in
         * the register, still registered for the exam.
         */
        return DB::transaction(function () use ($student) {
            if ($this->enrollments->openPeriodOf($student)) {
                $this->enrollments->leave(
                    $student,
                    null,
                    null,
                    'Record deleted.'
                );
            }

            return (bool) $student->delete();
        });
    }

    /**
     * Restore a soft-deleted student.
     */
    public function restore(Student $student): bool
    {
        return (bool) $student->restore();
    }

    /**
     * Force delete a student.
     */
    public function forceDelete(Student $student): bool
    {
        return (bool) $student->forceDelete();
    }

    /**
     * Export students data.
     */
    public function export(Request $request)
    {
        return $this->exports->stream(
            $request->user(),
            $request->only(['campus_id', 'class_id', 'section_id'])
        );
    }

    /**
     * Import students from file.
     */
    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'dry_run' => ['nullable', 'boolean'],
        ]);

        $report = $request->boolean('dry_run')
            ? $this->imports->dryRun($validated['file'])
            : $this->imports->import($validated['file']);

        return response()->json([
            'success' => $report['ok'],
            'message' => $this->importMessage($report, $request->boolean('dry_run')),
            'data' => $report,
        ], $report['ok'] ? 200 : 422);
    }

    /**
     * What to tell the person who uploaded the file.
     *
     * @param  array<string, mixed>  $report
     */
    private function importMessage(array $report, bool $dryRun): string
    {
        if (! $report['ok']) {
            $count = count($report['problems']);

            return $count.($count === 1 ? ' problem' : ' problems')
                .' found. Nothing was imported.';
        }

        if ($dryRun) {
            return ($report['would_import'] ?? 0).' children are ready to import.';
        }

        return $report['imported'].' children imported.';
    }
}
