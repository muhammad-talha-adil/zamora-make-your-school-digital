<?php

namespace App\Services;

use App\Http\Resources\StudentResource;
use App\Models\Section;
use App\Models\Student;
use App\Repositories\StudentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Response;

class StudentService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        protected StudentRepository $repository
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
                ->first(fn($r) => $r->leave_date === null);

            if (!$enrollment) {
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
                $query->with(['campus', 'class', 'section', 'session', 'studentStatus'])
                    ->orderBy('admission_date', 'desc');
            },
        ]);

        // Get the current active enrollment or most recent enrollment
        $currentEnrollment = $student->enrollmentRecords
            ->sortByDesc('admission_date')
            ->first(fn($r) => $r->leave_date === null);

        if (!$currentEnrollment) {
            $currentEnrollment = $student->enrollmentRecords->sortByDesc('admission_date')->first();
        }

        $lookupData = $this->repository->getLookupData();

        // Get ALL sections for the class dropdown filtering in Vue
        // (Edit.vue filters sections by class_id on the frontend when class changes)
        $sections = Section::orderBy('name')->get(['id', 'name', 'class_id']);

        // Build the student object exactly as Edit.vue expects
        $studentData = [
            'id' => $student->id,
            'admission_no' => $student->admission_no,
            'registration_no' => $student->registration_no,
            'name' => $student->user?->name,
            'dob' => $student->dob?->format('Y-m-d'),
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
            $studentData['admission_date'] = $currentEnrollment->admission_date?->format('Y-m-d');
            $studentData['current_enrollment'] = [
                'monthly_fee' => $currentEnrollment->monthly_fee,
                'annual_fee' => $currentEnrollment->annual_fee,
                // NEW: Fee structure integration fields
                'fee_structure_id' => $currentEnrollment->fee_structure_id,
                'fee_mode' => $currentEnrollment->fee_mode,
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
                'id' => $studentGuardian->guardian?->id ?? 0,
                'name' => $studentGuardian->guardian?->user?->name,
                'phone' => $studentGuardian->guardian?->phone,
                'email' => $studentGuardian->guardian?->user?->email,
                'cnic' => $studentGuardian->guardian?->cnic,
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
            'school' => \App\Models\School::first(),
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
        Log::info('StudentService: Creating student', [
            'user_id' => auth()->id(),
            'admission_no' => $data['admission_no'],
        ]);

        $student = $this->repository->createWithRelationships($data);

        Log::info('StudentService: Student created successfully', [
            'student_id' => $student->id,
            'student_code' => $student->student_code,
        ]);

        return $student;
    }

    /**
     * Update a student.
     */
    public function update(Student $student, array $data): Student
    {
        Log::info('StudentService: Updating student', [
            'user_id' => auth()->id(),
            'student_id' => $student->id,
        ]);

        $student = $this->repository->updateWithRelationships($student, $data);

        Log::info('StudentService: Student updated successfully', [
            'student_id' => $student->id,
        ]);

        return $student;
    }

    /**
     * Change student status.
     */
    public function changeStatus(Student $student, array $data): Student
    {
        Log::info('StudentService: Changing student status', [
            'user_id' => auth()->id(),
            'student_id' => $student->id,
            'is_reactivation' => $data['is_reactivation'] ?? false,
        ]);

        $student = $this->repository->changeStatus($student, $data);

        Log::info('StudentService: Student status changed successfully', [
            'student_id' => $student->id,
            'new_status_id' => $student->student_status_id,
        ]);

        return $student;
    }

    /**
     * Re-admit a student.
     */
    public function readmit(Student $student, array $data): Student
    {
        Log::info('StudentService: Re-admitting student', [
            'user_id' => auth()->id(),
            'student_id' => $student->id,
        ]);

        $student = $this->repository->readmit($student, $data);

        Log::info('StudentService: Student re-admitted successfully', [
            'student_id' => $student->id,
        ]);

        return $student;
    }

    /**
     * Delete a student (soft delete).
     */
    public function delete(Student $student): bool
    {
        Log::info('StudentService: Deleting student', [
            'user_id' => auth()->id(),
            'student_id' => $student->id,
        ]);

        $deleted = $student->delete();

        if ($deleted) {
            Log::info('StudentService: Student deleted successfully', [
                'student_id' => $student->id,
            ]);
        }

        return $deleted;
    }

    /**
     * Restore a soft-deleted student.
     */
    public function restore(Student $student): bool
    {
        Log::info('StudentService: Restoring student', [
            'user_id' => auth()->id(),
            'student_id' => $student->id,
        ]);

        $restored = $student->restore();

        if ($restored) {
            Log::info('StudentService: Student restored successfully', [
                'student_id' => $student->id,
            ]);
        }

        return $restored;
    }

    /**
     * Force delete a student.
     */
    public function forceDelete(Student $student): bool
    {
        Log::info('StudentService: Force deleting student', [
            'user_id' => auth()->id(),
            'student_id' => $student->id,
        ]);

        $deleted = $student->forceDelete();

        if ($deleted) {
            Log::info('StudentService: Student force deleted', [
                'student_id' => $student->id,
            ]);
        }

        return $deleted;
    }

    /**
     * Export students data.
     */
    public function export(Request $request): JsonResponse
    {
        Log::info('StudentService: Exporting students', [
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Export started. You will be notified when ready.',
        ]);
    }

    /**
     * Import students from file.
     */
    public function import(Request $request): JsonResponse
    {
        Log::info('StudentService: Importing students', [
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Import started. You will be notified when complete.',
        ]);
    }
}
