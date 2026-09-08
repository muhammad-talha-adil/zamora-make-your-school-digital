<?php

namespace App\Repositories;

use App\Enums\Fee\ApprovalStatus;
use App\Models\Campus;
use App\Models\Fee\DiscountType;
use App\Models\Fee\StudentDiscount;
use App\Models\Gender;
use App\Models\Guardian;
use App\Models\Relation;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\StudentGuardian;
use App\Models\StudentStatus;
use App\Models\User;
use App\Services\GuardianService;
use App\Services\StudentUserService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StudentRepository
{
    /**
     * Cache keys for student-related data.
     */
    private const CACHE_KEY_STUDENTS = 'students.list';

    private const CACHE_KEY_DROPDOWNS = 'students.dropdowns';

    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Eager loading relationships for students.
     */
    private const WITH_RELATIONS = [
        'user',
        'gender',
        'studentStatus',
        'studentGuardians.guardian.user',
        'studentGuardians.relation',
        'enrollmentRecords.campus',
        'enrollmentRecords.class',
        'enrollmentRecords.section',
        'enrollmentRecords.session',
    ];

    /**
     * Create a new repository instance.
     */
    public function __construct(
        protected StudentUserService $studentUserService,
        protected GuardianService $guardianService
    ) {}

    /**
     * Get paginated students with filters and eager loading.
     */
    public function getPaginatedWithFilters(array $filters, int $perPage = 10, ?int $page = null): LengthAwarePaginator
    {
        // Get current page from request if not provided
        $page = $page ?? request()->get('page', 1);
        $cacheKey = self::CACHE_KEY_STUDENTS.':'.md5(json_encode($filters).$perPage.$page);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters, $perPage) {
            $query = Student::with(self::WITH_RELATIONS);

            // Filter by enrollment's campus
            if (! empty($filters['campus_id'])) {
                $query->whereHas('enrollmentRecords', function ($q) use ($filters) {
                    $q->where('campus_id', $filters['campus_id'])
                        ->whereNull('leave_date');
                });
            }

            // Filter by enrollment's class
            if (! empty($filters['class_id'])) {
                $query->whereHas('enrollmentRecords', function ($q) use ($filters) {
                    $q->where('class_id', $filters['class_id'])
                        ->whereNull('leave_date');
                });
            }

            // Filter by enrollment's section
            if (! empty($filters['section_id'])) {
                $query->whereHas('enrollmentRecords', function ($q) use ($filters) {
                    $q->where('section_id', $filters['section_id'])
                        ->whereNull('leave_date');
                });
            }

            // Filter by gender
            if (! empty($filters['gender_id'])) {
                $query->where('gender_id', $filters['gender_id']);
            }

            // Filter by status
            if (! empty($filters['status'])) {
                $query->where('student_status_id', $filters['status']);
            }

            // Search by name, registration_no, admission_no, guardian name, guardian phone
            if (! empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('registration_no', 'like', "%{$search}%")
                        ->orWhere('admission_no', 'like', "%{$search}%")
                        ->orWhere('b_form', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('studentGuardians', function ($guardianQuery) use ($search) {
                            $guardianQuery->whereHas('guardian', function ($g) use ($search) {
                                $g->where('phone', 'like', "%{$search}%")
                                    ->orWhereHas('user', function ($gu) use ($search) {
                                        $gu->where('name', 'like', "%{$search}%");
                                    });
                            });
                        });
                });
            }

            return $query->orderBy('id', 'desc')
                ->paginate($perPage)
                ->withQueryString();
        });
    }

    /**
     * Get students for dropdowns with minimal data.
     */
    public function getForDropdown(array $filters = []): Collection
    {
        $cacheKey = self::CACHE_KEY_DROPDOWNS.':'.md5(json_encode($filters));

        return Cache::remember($cacheKey, 1800, function () use ($filters) { // 30 minutes
            $query = Student::with(['user:id,name', 'currentEnrollment']);

            if (! empty($filters['campus_id'])) {
                $query->whereHas('currentEnrollment', function ($q) use ($filters) {
                    $q->where('campus_id', $filters['campus_id']);
                });
            }

            if (! empty($filters['class_id'])) {
                $query->whereHas('currentEnrollment', function ($q) use ($filters) {
                    $q->where('class_id', $filters['class_id']);
                });
            }

            return $query->orderBy('registration_no')
                ->get(['id', 'registration_no', 'admission_no']);
        });
    }

    /**
     * Get a student by ID with all relationships.
     */
    public function findById(int $id): ?Student
    {
        return Student::with(self::WITH_RELATIONS)
            ->find($id);
    }

    /**
     * Get a student by admission number.
     */
    public function findByAdmissionNo(string $admissionNo): ?Student
    {
        return Student::with(self::WITH_RELATIONS)
            ->where('admission_no', $admissionNo)
            ->first();
    }

    /**
     * Get a student by registration number.
     */
    public function findByRegistrationNo(string $registrationNo): ?Student
    {
        return Student::with(self::WITH_RELATIONS)
            ->where('registration_no', $registrationNo)
            ->first();
    }

    /**
     * Generate unique admission number.
     */
    public function generateAdmissionNumber(): string
    {
        $lastStudent = Student::orderBy('id', 'desc')->first();
        $nextNumber = $lastStudent ? (int) substr($lastStudent->admission_no, 4) + 1 : 1;

        return 'ADM-'.str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique student code.
     */
    public function generateStudentCode(): string
    {
        $maxStudentCode = Student::where('student_code', 'like', 'STU-%')
            ->max('student_code');
        $nextNumber = $maxStudentCode ? (int) str_replace('STU-', '', $maxStudentCode) + 1 : 1;

        return 'STU-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique registration number.
     */
    public function generateRegistrationNumber(): string
    {
        $maxId = Student::max('id') ?? 0;

        return 'REG-'.date('Y').'-'.str_pad((string) ($maxId + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * Handle student image upload.
     * Saves image with format: studentname_studentcode.extension
     */
    private function handleImageUpload($imageFile, string $studentName, string $studentCode): ?string
    {
        if (! $imageFile) {
            return null;
        }

        // Sanitize student name for filename
        $sanitizedName = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower(trim($studentName)));
        $sanitizedName = preg_replace('/_+/', '_', $sanitizedName);

        // Get file extension
        $extension = $imageFile->getClientOriginalExtension();

        // Create filename: studentname_studentcode.extension
        $filename = $sanitizedName.'_'.$studentCode.'.'.$extension;

        // Ensure the students directory exists
        $directory = 'students';
        if (! Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        // Store in public/students directory
        $path = $imageFile->storeAs($directory, $filename, 'public');

        return $path;
    }

    /**
     * Delete student image.
     */
    private function deleteImage(?string $imagePath): void
    {
        if ($imagePath && Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }
    }

    /**
     * Create a new student with enrollment and guardians.
     */
    public function createWithRelationships(array $data): Student
    {
        return DB::transaction(function () use ($data) {
            // 1. Generate unique student code and registration number
            $studentCode = $this->generateStudentCode();
            $registrationNo = $this->generateRegistrationNumber();

            // 2. Create student user account
            $studentUser = $this->studentUserService->createStudentUser(
                $data['name'],
                $data['student_email'] ?? null,
                $studentCode
            );

            // 3. Handle image upload
            $imagePath = $this->handleImageUpload($data['image'] ?? null, $data['name'], $studentCode);

            // 4. Create Student record
            $student = Student::create([
                'user_id' => $studentUser->id,
                'registration_no' => $registrationNo,
                'student_code' => $studentCode,
                'admission_no' => $data['admission_no'],
                'dob' => $data['dob'],
                'gender_id' => $data['gender_id'],
                'student_status_id' => $data['student_status_id'],
                'b_form' => $data['b_form'] ?? null,
                'description' => $data['description'] ?? null,
                'admission_date' => $data['admission_date'] ?? now()->toDateString(),
                'image' => $imagePath,
            ]);

            // 5. Create enrollment record
            $enrollment = StudentEnrollmentRecord::create([
                'student_id' => $student->id,
                'session_id' => $data['session_id'],
                'class_id' => $data['class_id'],
                'section_id' => $data['section_id'] ?? null,
                'campus_id' => $data['campus_id'],
                'admission_date' => $data['admission_date'] ?? now()->toDateString(),
                'leave_date' => null,
                'student_status_id' => $data['student_status_id'],
                'previous_enrollment_id' => null,
                'description' => $data['description'] ?? null,
                'monthly_fee' => $data['monthly_fee'] ?? 0,
                'annual_fee' => $data['annual_fee'] ?? 0,
                // New fee structure integration fields
                'fee_structure_id' => $data['fee_structure_id'] ?? null,
                'fee_mode' => $data['fee_mode'] ?? null,
                'custom_fee_entries' => $data['custom_fee_entries'] ?? null,
                'manual_discount_percentage' => $data['manual_discount_percentage'] ?? null,
                'manual_discount_reason' => $data['manual_discount_reason'] ?? null,
            ]);

            // The fee agreed at admission lives on the enrollment itself
            // (`fee_mode`, `custom_fee_entries`, `monthly_fee`, `annual_fee`).
            // It used to be copied into `student_fee_assignments` as well,
            // which left the same figure in two places with nothing saying
            // which one billing should trust. That table is now reserved for
            // deliberate mid-session overrides.

            // 8. Create student discounts if fee_mode is 'discount' or 'manual'
            if (($data['fee_mode'] ?? null) === 'discount' && ! empty($data['discounts'])) {
                $this->createDiscountsFromAdmission($enrollment, $data);
            }

            // 8. Handle Father Guardian
            $fatherGuardian = $this->handleFatherGuardian($data, $student);

            // 7. Handle Mother Guardian if provided
            if (! empty($data['mother_name'])) {
                $this->handleMotherGuardian($data, $student);
            }

            // 8. Handle Other Guardian if provided
            if (! empty($data['other_name'])) {
                $this->handleOtherGuardian($data, $student);
            }

            Log::info('Student created successfully', [
                'student_id' => $student->id,
                'student_code' => $studentCode,
                'admission_no' => $data['admission_no'],
                'user_id' => auth()->id(),
            ]);

            return $student;
        });
    }

    /**
     * Handle father guardian creation.
     */
    private function handleFatherGuardian(array $data, Student $student): Guardian
    {
        // Check if guardian_id is provided (linking existing guardian).
        //
        // Cast to int: `find()` given an array returns a Collection, so an
        // unexpected shape would otherwise flow on as one.
        if (! empty($data['guardian_id'])) {
            $fatherGuardian = Guardian::findOrFail((int) $data['guardian_id']);
        } else {
            $fatherPhone = ! empty($data['father_phone']) ? $data['father_phone'] : null;
            $fatherGuardian = $this->guardianService->findOrCreateByPhone($fatherPhone, [
                'name' => $data['father_name'],
                'email' => $data['father_email'] ?? null,
                'phone' => $fatherPhone,
                'cnic' => $data['father_cnic'] ?? null,
                'occupation' => $data['father_occupation'] ?? null,
                'address' => $data['father_address'] ?? null,
            ]);
        }

        // Link father as primary guardian.
        //
        // `father_relation_id` is only required alongside `father_name`, so an
        // admission that links an existing guardian — the sibling case — does
        // not carry it. Reading it unconditionally raised an undefined-key
        // error and rolled the whole admission back.
        StudentGuardian::create([
            'student_id' => $student->id,
            'guardian_id' => $fatherGuardian->id,
            'relation_id' => $data['father_relation_id']
                ?? $this->existingRelationFor($fatherGuardian),
            'is_primary' => true,
        ]);

        return $fatherGuardian;
    }

    /**
     * The relation an already-linked guardian is recorded under elsewhere.
     *
     * Falls back to the guardian's most recent link so a sibling admission
     * keeps the same relationship the family already has on file.
     */
    private function existingRelationFor(Guardian $guardian): ?int
    {
        return StudentGuardian::where('guardian_id', $guardian->id)
            ->latest('id')
            ->value('relation_id');
    }

    /**
     * Handle mother guardian creation.
     */
    private function handleMotherGuardian(array $data, Student $student): void
    {
        $motherPhone = (! empty($data['mother_phone_different']) && ! empty($data['mother_phone']))
            ? $data['mother_phone']
            : null;

        $motherGuardian = $this->guardianService->findOrCreateByPhone($motherPhone ?? '', [
            'name' => $data['mother_name'],
            'email' => $data['mother_email'] ?? null,
            'phone' => $motherPhone,
            'cnic' => $data['mother_cnic'] ?? null,
        ]);

        StudentGuardian::create([
            'student_id' => $student->id,
            'guardian_id' => $motherGuardian->id,
            'relation_id' => $data['mother_relation_id'] ?? 2,
            'is_primary' => false,
        ]);
    }

    /**
     * Handle other guardian creation.
     */
    private function handleOtherGuardian(array $data, Student $student): void
    {
        $otherGuardian = $this->guardianService->findOrCreateByPhone($data['other_phone'] ?? '', [
            'name' => $data['other_name'],
            'email' => $data['other_email'] ?? null,
            'phone' => $data['other_phone'] ?? null,
            'cnic' => $data['other_cnic'] ?? null,
        ]);

        StudentGuardian::create([
            'student_id' => $student->id,
            'guardian_id' => $otherGuardian->id,
            'relation_id' => $data['other_relation_id'] ?? 3,
            'is_primary' => false,
        ]);
    }

    /**
     * Update a student with enrollment and guardians.
     */
    public function updateWithRelationships(Student $student, array $data): Student
    {
        return DB::transaction(function () use ($student, $data) {
            // 1. Update student identity info
            $student->update([
                'admission_no' => $data['admission_no'],
                'dob' => $data['dob'],
                'gender_id' => $data['gender_id'],
                'student_status_id' => $data['student_status_id'],
                'b_form' => $data['b_form'] ?? null,
                'description' => $data['description'] ?? null,
                'admission_date' => $data['admission_date'] ?? $student->admission_date,
            ]);

            if ($student->user && ! empty($data['name'])) {
                $this->studentUserService->syncManagedStudentIdentity($student->user, $data['name']);
            }

            // 2. Handle image upload, update, or removal
            $this->handleImageUpdate($student, $data);

            // 3. Update or create current enrollment record
            $currentEnrollment = $student->currentEnrollment;

            if ($currentEnrollment) {
                if ($this->isPlacementChange($currentEnrollment, $data)) {
                    // A move to another class, section, campus or session opens
                    // a new period rather than overwriting the old one, so the
                    // record still shows where the child sat, and on what fee,
                    // for each part of the year.
                    $currentEnrollment = $this->openNewPeriod($student, $currentEnrollment, $data);
                } else {
                    $currentEnrollment->update([
                        'admission_date' => $data['admission_date'] ?? $currentEnrollment->admission_date,
                        'student_status_id' => $data['student_status_id'],
                        'description' => $data['description'] ?? null,
                        'monthly_fee' => $data['monthly_fee'] ?? 0,
                        'annual_fee' => $data['annual_fee'] ?? 0,
                        // NEW: Fee structure integration fields
                        'fee_structure_id' => $data['fee_structure_id'] ?? null,
                        'fee_mode' => $data['fee_mode'] ?? 'structure',
                        'custom_fee_entries' => $data['custom_fee_entries'] ?? null,
                        'manual_discount_percentage' => $data['manual_discount_percentage'] ?? null,
                        'manual_discount_reason' => $data['manual_discount_reason'] ?? null,
                    ]);
                }

                $currentEnrollment->discounts()->delete();
                if (($data['fee_mode'] ?? 'structure') === 'discount' && ! empty($data['discounts'])) {
                    $this->createDiscountsFromAdmission($currentEnrollment, $data);
                }
            } else {
                $currentEnrollment = StudentEnrollmentRecord::create([
                    'student_id' => $student->id,
                    'session_id' => $data['session_id'],
                    'class_id' => $data['class_id'],
                    'section_id' => $data['section_id'] ?? null,
                    'campus_id' => $data['campus_id'],
                    'admission_date' => $data['admission_date'] ?? now()->toDateString(),
                    'leave_date' => null,
                    'student_status_id' => $data['student_status_id'],
                    'previous_enrollment_id' => null,
                    'description' => $data['description'] ?? null,
                    'monthly_fee' => $data['monthly_fee'] ?? 0,
                    'annual_fee' => $data['annual_fee'] ?? 0,
                    // NEW: Fee structure integration fields
                    'fee_structure_id' => $data['fee_structure_id'] ?? null,
                    'fee_mode' => $data['fee_mode'] ?? 'structure',
                    'custom_fee_entries' => $data['custom_fee_entries'] ?? null,
                    'manual_discount_percentage' => $data['manual_discount_percentage'] ?? null,
                    'manual_discount_reason' => $data['manual_discount_reason'] ?? null,
                ]);

                if (($data['fee_mode'] ?? 'structure') === 'discount' && ! empty($data['discounts'])) {
                    $this->createDiscountsFromAdmission($currentEnrollment, $data);
                }
            }

            $this->syncAdmissionGuardians($student, $data);

            Log::info('Student updated successfully', [
                'student_id' => $student->id,
                'user_id' => auth()->id(),
            ]);

            return $student;
        });
    }

    /**
     * Handle student image update, upload, or removal.
     */
    private function handleImageUpdate(Student $student, array $data): void
    {
        // Get student's name from user relationship
        $studentName = $student->user?->name ?? $student->student_code;

        // Check if image should be removed
        if (! empty($data['remove_image'])) {
            // Delete old image
            $this->deleteImage($student->image);
            $student->update(['image' => null]);

            return;
        }

        // Check if new image is uploaded
        if (! empty($data['image']) && $data['image'] instanceof UploadedFile) {
            // Delete old image if exists
            $this->deleteImage($student->image);

            // Upload new image with student name and code
            $newImagePath = $this->handleImageUpload($data['image'], $studentName, $student->student_code);
            $student->update(['image' => $newImagePath]);
        }
    }

    /**
     * Whether the submitted placement differs from where the student sits now.
     *
     * Fee amounts and descriptions are edits to the current period; a change of
     * class, section, campus or session is a move to a new one.
     *
     * @param  array<string, mixed>  $data
     */
    private function isPlacementChange(StudentEnrollmentRecord $current, array $data): bool
    {
        $submitted = [
            'session_id' => $data['session_id'] ?? null,
            'class_id' => $data['class_id'] ?? null,
            'section_id' => $data['section_id'] ?? null,
            'campus_id' => $data['campus_id'] ?? null,
        ];

        foreach ($submitted as $column => $value) {
            if ($value === null) {
                continue;
            }

            if ((int) $current->{$column} !== (int) $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Closes the current enrollment period and opens the next one.
     *
     * The closed row keeps the class, section and fee the student was on, and
     * the new row points back at it, so the year reads as a sequence of periods
     * rather than a single row that has been overwritten.
     *
     * @param  array<string, mixed>  $data
     */
    private function openNewPeriod(
        Student $student,
        StudentEnrollmentRecord $current,
        array $data
    ): StudentEnrollmentRecord {
        $movedOn = $data['admission_date'] ?? now()->toDateString();

        // A period cannot end before it began; a back-dated move closes the
        // previous row on its own start date, leaving a zero-length period.
        $closedOn = max($movedOn, $current->admission_date->toDateString());

        $current->update(['leave_date' => $closedOn]);

        return StudentEnrollmentRecord::create([
            'student_id' => $student->id,
            'session_id' => $data['session_id'],
            'class_id' => $data['class_id'],
            'section_id' => $data['section_id'] ?? null,
            'campus_id' => $data['campus_id'],
            'admission_date' => $movedOn,
            'leave_date' => null,
            'student_status_id' => $data['student_status_id'],
            'previous_enrollment_id' => $current->id,
            'description' => $data['description'] ?? null,
            'monthly_fee' => $data['monthly_fee'] ?? 0,
            'annual_fee' => $data['annual_fee'] ?? 0,
            'fee_structure_id' => $data['fee_structure_id'] ?? null,
            'fee_mode' => $data['fee_mode'] ?? 'structure',
            'custom_fee_entries' => $data['custom_fee_entries'] ?? null,
            'manual_discount_percentage' => $data['manual_discount_percentage'] ?? null,
            'manual_discount_reason' => $data['manual_discount_reason'] ?? null,
        ]);
    }

    /**
     * Change student status (leave or re-admission).
     */
    public function changeStatus(Student $student, array $data): Student
    {
        DB::transaction(function () use ($student, $data) {
            if (! empty($data['is_reactivation'])) {
                // Re-admission
                $this->handleReactivation($student, $data);
            } else {
                // Leave
                $this->handleLeave($student, $data);
            }

            Log::info('Student status changed', [
                'student_id' => $student->id,
                'is_reactivation' => $data['is_reactivation'] ?? false,
                'user_id' => auth()->id(),
            ]);

            return $student;
        });

        // `fresh()` is null only if the row vanished mid-request; the caller is
        // promised a Student, so fall back to the instance in hand.
        return $student->fresh() ?? $student;
    }

    /**
     * Handle student re-admission.
     */
    private function handleReactivation(Student $student, array $data): void
    {
        $lastEnrollment = StudentEnrollmentRecord::where('student_id', $student->id)
            ->orderBy('id', 'desc')
            ->first();

        $sessionId = $data['session_id'] ?? $lastEnrollment?->session_id;
        $classId = $data['class_id'] ?? $lastEnrollment?->class_id;
        $sectionId = $data['section_id'] ?? $lastEnrollment?->section_id;
        $campusId = $data['campus_id'] ?? $lastEnrollment?->campus_id;

        if (! $sessionId || ! $classId || ! $sectionId) {
            throw new \RuntimeException('Session, class, and section are required for re-admission');
        }

        StudentEnrollmentRecord::create([
            'student_id' => $student->id,
            'session_id' => $sessionId,
            'class_id' => $classId,
            'section_id' => $sectionId,
            'campus_id' => $campusId,
            'admission_date' => now()->toDateString(),
            'leave_date' => null,
            'student_status_id' => $data['status_id'] ?? StudentStatus::where('name', 'Active')->first()?->id,
            'previous_enrollment_id' => $lastEnrollment?->id,
        ]);

        $student->update([
            'student_status_id' => $data['status_id'] ?? StudentStatus::where('name', 'Active')->first()?->id,
        ]);

        // Activate user account
        if ($student->user_id) {
            User::where('id', $student->user_id)->update(['is_active' => true]);
        }
    }

    /**
     * Handle student leave.
     */
    private function handleLeave(Student $student, array $data): void
    {
        $currentEnrollment = StudentEnrollmentRecord::where('student_id', $student->id)
            ->whereNull('leave_date')
            ->first();

        if ($currentEnrollment) {
            $currentEnrollment->update([
                'leave_date' => now()->toDateString(),
                'student_status_id' => $data['status_id'],
                'description' => $data['status_description'] ?? null,
            ]);
        } else {
            StudentEnrollmentRecord::create([
                'student_id' => $student->id,
                'admission_date' => now()->toDateString(),
                'leave_date' => now()->toDateString(),
                'student_status_id' => $data['status_id'],
                'description' => $data['status_description'] ?? null,
            ]);
        }

        $student->update([
            'student_status_id' => $data['status_id'],
        ]);

        // Deactivate user account
        if ($student->user_id) {
            User::where('id', $student->user_id)->update(['is_active' => false]);
        }
    }

    /**
     * Re-admit a previously left student.
     */
    public function readmit(Student $student, array $data): Student
    {
        DB::transaction(function () use ($student, $data) {
            $lastEnrollment = StudentEnrollmentRecord::where('student_id', $student->id)
                ->orderBy('id', 'desc')
                ->first();

            $activeStatusId = StudentStatus::where('name', 'Active')->first()?->id
                ?? StudentStatus::where('name', 'Left')->first()?->id
                ?? 2;

            StudentEnrollmentRecord::create([
                'student_id' => $student->id,
                'session_id' => $data['session_id'],
                'class_id' => $data['class_id'],
                'section_id' => $data['section_id'],
                'campus_id' => $data['campus_id'],
                'admission_date' => $data['admission_date'] ?? now()->toDateString(),
                'leave_date' => null,
                'student_status_id' => $activeStatusId,
                'previous_enrollment_id' => $lastEnrollment?->id,
            ]);

            $student->update([
                'student_status_id' => $activeStatusId,
            ]);

            // Activate user account
            if ($student->user_id) {
                User::where('id', $student->user_id)->update(['is_active' => true]);
            }

            Log::info('Student re-admitted', [
                'student_id' => $student->id,
                'user_id' => auth()->id(),
            ]);

            return $student;
        });

        // `fresh()` is null only if the row vanished mid-request; the caller is
        // promised a Student, so fall back to the instance in hand.
        return $student->fresh() ?? $student;
    }

    /**
     * Get guardian by phone number.
     * Optimized query - uses normalized phone comparison for faster results.
     */
    public function getGuardianByPhone(string $phone): ?Guardian
    {
        // Normalize phone number by removing all non-digit characters
        $cleanPhone = preg_replace('/\D/', '', $phone);

        // Try exact match first (most common case), then partial matches
        $guardian = Guardian::where('phone', $cleanPhone)
            ->orWhere('phone', 'LIKE', '%'.$cleanPhone)
            ->with(['user:id,name,email', 'students'])
            ->first();

        return $guardian;
    }

    /**
     * Get sections by class for dropdown.
     */
    public function getSectionsByClass(int $classId): Collection
    {
        return Section::where('class_id', $classId)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Create student discounts from admission fee discounts.
     * This handles applying discount types to students.
     */
    private function createDiscountsFromAdmission(StudentEnrollmentRecord $enrollment, array $data): void
    {
        $discounts = $data['discounts'] ?? [];

        foreach ($discounts as $discountData) {
            // Get the discount type to check if it requires approval
            $discountTypeId = $discountData['discount_type_id'] ?? null;
            $requiresApproval = false;

            if ($discountTypeId) {
                $discountType = DiscountType::find($discountTypeId);
                $requiresApproval = $discountType?->requires_approval ?? false;
            }

            StudentDiscount::create([
                'student_id' => $enrollment->student_id,
                'student_enrollment_record_id' => $enrollment->id,
                'discount_type_id' => $discountTypeId,
                'fee_head_id' => $discountData['fee_head_id'] ?? null,
                'value_type' => $discountData['value_type'] ?? 'percent',
                'value' => $discountData['value'] ?? 0,
                'effective_from' => $enrollment->admission_date ?? now()->toDateString(),
                'effective_to' => null,
                'approval_status' => $requiresApproval ? ApprovalStatus::PENDING : ApprovalStatus::APPROVED,
                'approved_by' => $requiresApproval ? null : auth()->id(),
                'reason' => 'Applied at admission',
            ]);
        }

        Log::info('Discounts created from admission', [
            'student_id' => $enrollment->student_id,
            'enrollment_id' => $enrollment->id,
            'discounts_count' => count($discounts),
        ]);
    }

    private function syncAdmissionGuardians(Student $student, array $data): void
    {
        $student->loadMissing('studentGuardians.guardian.user');

        $this->syncPrimaryGuardian($student, $data);
        $this->syncOtherGuardian($student, $data);
    }

    private function syncPrimaryGuardian(Student $student, array $data): void
    {
        $primaryLink = $student->studentGuardians->firstWhere('is_primary', true);

        // An update that only touches the student or their placement carries no
        // guardian fields, and there is nothing to sync.
        if (empty($data['father_name']) && empty($data['guardian_id'])) {
            return;
        }

        $payload = [
            'name' => $data['father_name'] ?? $primaryLink?->guardian?->user?->name,
            'email' => $data['father_email'] ?? null,
            'phone' => $data['father_phone'] ?? null,
            'cnic' => $data['father_cnic'] ?? null,
            'occupation' => $data['father_occupation'] ?? null,
            'address' => $data['father_address'] ?? null,
        ];

        if (! empty($data['guardian_id'])) {
            $guardian = Guardian::findOrFail($data['guardian_id']);
            $this->guardianService->updateGuardian($guardian, $payload);
        } else {
            $guardian = $this->resolveGuardianForUpdate($primaryLink?->guardian, $payload);
        }

        if ($primaryLink) {
            $primaryLink->update([
                'guardian_id' => $guardian->id,
                'relation_id' => $data['father_relation_id'] ?? $primaryLink->relation_id,
                'is_primary' => true,
            ]);
        } else {
            StudentGuardian::create([
                'student_id' => $student->id,
                'guardian_id' => $guardian->id,
                'relation_id' => $data['father_relation_id'],
                'is_primary' => true,
            ]);
        }

        $otherLinks = StudentGuardian::where('student_id', $student->id);
        if ($primaryLink) {
            $otherLinks->where('id', '!=', $primaryLink->id);
        }
        $otherLinks->update(['is_primary' => false]);
    }

    private function syncOtherGuardian(Student $student, array $data): void
    {
        $otherLink = $student->studentGuardians->first(fn ($link) => ! $link->is_primary);

        if (empty($data['other_name'])) {
            StudentGuardian::where('student_id', $student->id)
                ->where('is_primary', false)
                ->delete();

            return;
        }

        $payload = [
            'name' => $data['other_name'],
            'email' => $data['other_email'] ?? null,
            'phone' => $data['other_phone'] ?? null,
            'cnic' => $data['other_cnic'] ?? null,
        ];

        $guardian = $this->resolveGuardianForUpdate($otherLink?->guardian, $payload);

        if ($otherLink) {
            $otherLink->update([
                'guardian_id' => $guardian->id,
                'relation_id' => $data['other_relation_id'] ?? $otherLink->relation_id,
                'is_primary' => false,
            ]);
        } else {
            StudentGuardian::create([
                'student_id' => $student->id,
                'guardian_id' => $guardian->id,
                'relation_id' => $data['other_relation_id'] ?? 3,
                'is_primary' => false,
            ]);
        }
    }

    /**
     * Resolves which guardian record an edit refers to.
     *
     * The edit form has one guardian section, so a changed phone almost always
     * means the number was corrected, not that a different person took over.
     * Matching strictly on phone treated it as a different person and created a
     * second guardian, splitting the family in two and leaving the sibling
     * lookup — which matches on phone — pointing at the abandoned record.
     *
     * A student is moved to a different guardian through `guardian_id`, which
     * the caller handles before this is reached.
     *
     * @param  array<string, mixed>  $payload
     */
    private function resolveGuardianForUpdate(?Guardian $existingGuardian, array $payload): Guardian
    {
        $cleanPhone = ! empty($payload['phone'])
            ? preg_replace('/[^0-9]/', '', $payload['phone'])
            : null;

        if ($existingGuardian) {
            // If the number now belongs to somebody already on file, that is a
            // genuine re-link rather than a correction.
            $holder = $cleanPhone
                ? Guardian::where('phone', $cleanPhone)
                    ->whereKeyNot($existingGuardian->getKey())
                    ->first()
                : null;

            if (! $holder) {
                $existingGuardian->update([
                    'phone' => $cleanPhone ?? $existingGuardian->phone,
                    'cnic' => $payload['cnic'] ?? $existingGuardian->cnic,
                    'occupation' => $payload['occupation'] ?? $existingGuardian->occupation,
                    'address' => $payload['address'] ?? $existingGuardian->address,
                ]);

                return $this->guardianService->updateGuardian($existingGuardian, $payload);
            }

            return $this->guardianService->updateGuardian($holder, $payload);
        }

        $guardian = $this->guardianService->findOrCreateByPhone($cleanPhone, $payload);

        return $this->guardianService->updateGuardian($guardian, $payload);
    }

    /**
     * Get lookup data for forms (campuses, classes, etc.).
     */
    public function getLookupData(): array
    {
        return Cache::remember('students.lookup_data', 1800, function () {
            return [
                'campuses' => Campus::orderBy('name')->get(['id', 'name']),
                'sessions' => Session::where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'classes' => SchoolClass::orderBy('id', 'ASC')->get(['id', 'name']),
                'sections' => Section::orderBy('name')->get(['id', 'name', 'class_id']),
                'genders' => Gender::orderBy('name')->get(['id', 'name']),
                'relations' => Relation::orderBy('name')->get(['id', 'name']),
                'studentStatuses' => StudentStatus::orderBy('name')->get(['id', 'name']),
            ];
        });
    }

    /**
     * Clear student-related caches.
     */
    public function clearCache(): void
    {
        Cache::flush();
    }

    /**
     * Chunk students for large dataset processing.
     */
    public function chunkForProcessing(int $chunkSize, callable $callback): void
    {
        Student::with(['user', 'enrollmentRecords'])
            ->chunkById($chunkSize, $callback);
    }
}
