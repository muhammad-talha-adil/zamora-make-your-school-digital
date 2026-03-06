<?php

namespace App\Repositories;

use App\Models\Campus;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        $cacheKey = self::CACHE_KEY_STUDENTS . ':' . md5(json_encode($filters) . $perPage . $page);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters, $perPage) {
            $query = Student::with(self::WITH_RELATIONS);

            // Filter by enrollment's campus
            if (!empty($filters['campus_id'])) {
                $query->whereHas('enrollmentRecords', function ($q) use ($filters) {
                    $q->where('campus_id', $filters['campus_id'])
                        ->whereNull('leave_date');
                });
            }

            // Filter by enrollment's class
            if (!empty($filters['class_id'])) {
                $query->whereHas('enrollmentRecords', function ($q) use ($filters) {
                    $q->where('class_id', $filters['class_id'])
                        ->whereNull('leave_date');
                });
            }

            // Filter by enrollment's section
            if (!empty($filters['section_id'])) {
                $query->whereHas('enrollmentRecords', function ($q) use ($filters) {
                    $q->where('section_id', $filters['section_id'])
                        ->whereNull('leave_date');
                });
            }

            // Filter by gender
            if (!empty($filters['gender_id'])) {
                $query->where('gender_id', $filters['gender_id']);
            }

            // Filter by status
            if (!empty($filters['status'])) {
                $query->where('student_status_id', $filters['status']);
            }

            // Search by name, registration_no, admission_no, guardian name, guardian phone
            if (!empty($filters['search'])) {
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
        $cacheKey = self::CACHE_KEY_DROPDOWNS . ':' . md5(json_encode($filters));

        return Cache::remember($cacheKey, 1800, function () use ($filters) { // 30 minutes
            $query = Student::with(['user:id,name', 'currentEnrollment']);

            if (!empty($filters['campus_id'])) {
                $query->whereHas('currentEnrollment', function ($q) use ($filters) {
                    $q->where('campus_id', $filters['campus_id']);
                });
            }

            if (!empty($filters['class_id'])) {
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
        return 'ADM-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique student code.
     */
    public function generateStudentCode(): string
    {
        $maxStudentCode = Student::where('student_code', 'like', 'STU-%')
            ->max('student_code');
        $nextNumber = $maxStudentCode ? (int) str_replace('STU-', '', $maxStudentCode) + 1 : 1;
        return 'STU-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique registration number.
     */
    public function generateRegistrationNumber(): string
    {
        $maxId = Student::max('id') ?? 0;
        return 'REG-' . date('Y') . '-' . str_pad($maxId + 1, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Handle student image upload.
     * Saves image with format: studentname_studentcode.extension
     */
    private function handleImageUpload($imageFile, string $studentName, string $studentCode): ?string
    {
        if (!$imageFile) {
            return null;
        }

        // Sanitize student name for filename
        $sanitizedName = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower(trim($studentName)));
        $sanitizedName = preg_replace('/_+/', '_', $sanitizedName);
        
        // Get file extension
        $extension = $imageFile->getClientOriginalExtension();
        
        // Create filename: studentname_studentcode.extension
        $filename = $sanitizedName . '_' . $studentCode . '.' . $extension;
        
        // Ensure the students directory exists
        $directory = 'students';
        if (!Storage::disk('public')->exists($directory)) {
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
                'admission_date' => now()->toDateString(),
                'image' => $imagePath,
            ]);

            // 5. Create enrollment record
            StudentEnrollmentRecord::create([
                'student_id' => $student->id,
                'session_id' => $data['session_id'],
                'class_id' => $data['class_id'],
                'section_id' => $data['section_id'],
                'campus_id' => $data['campus_id'],
                'admission_date' => now()->toDateString(),
                'leave_date' => null,
                'student_status_id' => $data['student_status_id'],
                'previous_enrollment_id' => null,
                'monthly_fee' => $data['monthly_fee'] ?? 0,
                'annual_fee' => $data['annual_fee'] ?? 0,
            ]);

            // 6. Handle Father Guardian
            $fatherGuardian = $this->handleFatherGuardian($data, $student);

            // 7. Handle Mother Guardian if provided
            if (!empty($data['mother_name'])) {
                $this->handleMotherGuardian($data, $student);
            }

            // 8. Handle Other Guardian if provided
            if (!empty($data['other_name'])) {
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
        // Check if guardian_id is provided (linking existing guardian)
        if (!empty($data['guardian_id'])) {
            $fatherGuardian = Guardian::find($data['guardian_id']);
            if (!$fatherGuardian) {
                throw new \RuntimeException('Guardian not found for the given ID');
            }
        } else {
            $fatherPhone = !empty($data['father_phone']) ? $data['father_phone'] : null;
            $fatherGuardian = $this->guardianService->findOrCreateByPhone($fatherPhone, [
                'name' => $data['father_name'],
                'email' => $data['father_email'] ?? null,
                'phone' => $fatherPhone,
                'cnic' => $data['father_cnic'] ?? null,
                'occupation' => $data['father_occupation'] ?? null,
                'address' => $data['father_address'] ?? null,
            ]);
        }

        // Link father as primary guardian
        StudentGuardian::create([
            'student_id' => $student->id,
            'guardian_id' => $fatherGuardian->id,
            'relation_id' => $data['father_relation_id'],
            'is_primary' => true,
        ]);

        return $fatherGuardian;
    }

    /**
     * Handle mother guardian creation.
     */
    private function handleMotherGuardian(array $data, Student $student): void
    {
        $motherPhone = (!empty($data['mother_phone_different']) && !empty($data['mother_phone']))
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
            ]);

            // 2. Handle image upload, update, or removal
            $this->handleImageUpdate($student, $data);

            // 3. Update or create current enrollment record
            $currentEnrollment = $student->currentEnrollment;

            if ($currentEnrollment) {
                $currentEnrollment->update([
                    'session_id' => $data['session_id'],
                    'class_id' => $data['class_id'],
                    'section_id' => $data['section_id'],
                    'campus_id' => $data['campus_id'],
                    'student_status_id' => $data['student_status_id'],
                    'monthly_fee' => $data['monthly_fee'] ?? 0,
                    'annual_fee' => $data['annual_fee'] ?? 0,
                ]);
            } else {
                StudentEnrollmentRecord::create([
                    'student_id' => $student->id,
                    'session_id' => $data['session_id'],
                    'class_id' => $data['class_id'],
                    'section_id' => $data['section_id'],
                    'campus_id' => $data['campus_id'],
                    'admission_date' => now()->toDateString(),
                    'leave_date' => null,
                    'student_status_id' => $data['student_status_id'],
                    'previous_enrollment_id' => null,
                ]);
            }

            // 4. Update guardians if provided
            if (!empty($data['guardians'])) {
                foreach ($data['guardians'] as $guardianData) {
                    if (isset($guardianData['id'])) {
                        $studentGuardian = StudentGuardian::where('student_id', $student->id)
                            ->where('guardian_id', $guardianData['id'])
                            ->first();

                        if ($studentGuardian) {
                            $studentGuardian->update([
                                'relation_id' => $guardianData['relation_id'],
                                'is_primary' => $guardianData['is_primary'] ?? false,
                            ]);
                        }

                        Guardian::where('id', $guardianData['id'])->update([
                            'phone' => $guardianData['phone'] ?? null,
                        ]);
                    }
                }
            }

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
        if (!empty($data['remove_image'])) {
            // Delete old image
            $this->deleteImage($student->image);
            $student->update(['image' => null]);
            return;
        }

        // Check if new image is uploaded
        if (!empty($data['image']) && $data['image'] instanceof \Illuminate\Http\File) {
            // Delete old image if exists
            $this->deleteImage($student->image);

            // Upload new image with student name and code
            $newImagePath = $this->handleImageUpload($data['image'], $studentName, $student->student_code);
            $student->update(['image' => $newImagePath]);
        }
    }

    /**
     * Change student status (leave or re-admission).
     */
    public function changeStatus(Student $student, array $data): Student
    {
        DB::transaction(function () use ($student, $data) {
            if (!empty($data['is_reactivation'])) {
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

        return $student->fresh();
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

        if (!$sessionId || !$classId || !$sectionId) {
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

        return $student->fresh();
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
            ->orWhere('phone', 'LIKE', '%' . $cleanPhone)
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
     * Get lookup data for forms (campuses, classes, etc.).
     */
    public function getLookupData(): array
    {
        return Cache::remember('students.lookup_data', 1800, function () {
            return [
                'campuses' => Campus::orderBy('name')->get(['id', 'name']),
                'sessions' => Session::where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'classes' => SchoolClass::orderBy('name')->get(['id', 'name']),
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
    public function chunkForProcessing(int $chunkSize = 100, callable $callback): void
    {
        Student::with(['user', 'enrollmentRecords'])
            ->chunkById($chunkSize, $callback);
    }
}
