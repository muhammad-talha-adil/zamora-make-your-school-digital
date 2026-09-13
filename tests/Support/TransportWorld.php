<?php

namespace Tests\Support;

use App\Models\Campus;
use App\Models\Permission;
use App\Models\StaffDepartment;
use App\Models\StaffDesignation;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\TransportStudentAssignment;
use App\Models\TransportVehicle;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

/**
 * A school with vehicles, routes, stops and enrolled students, for the
 * transport module's authorisation and data-integrity tests.
 *
 * Builds on AdmissionWorld so the campuses and academic structure are shared.
 */
class TransportWorld
{
    public AdmissionWorld $school;

    /**
     * The abilities the transport module is gated on.
     *
     * @var array<int, string>
     */
    public const ABILITIES = [
        'transport.view', 'transport.view.own',
        'transport.vehicle.manage', 'transport.route.manage',
        'transport.assignment.manage', 'transport.expense.manage',
    ];

    public function __construct()
    {
        $this->school = AdmissionWorld::make();

        $this->ensureAbilities();
    }

    public static function make(): self
    {
        return new self;
    }

    /**
     * Somebody who works here, holding the given abilities and pinned to a
     * campus through a staff record — `User::campusId()` reads it, and it is
     * what the transport policies' campus rule is built on.
     *
     * @param  array<int, string>  $abilities
     */
    public function person(string $name, array $abilities = [], ?Campus $campus = null): User
    {
        $user = User::create([
            'name' => $name,
            'username' => strtolower(str_replace(' ', '', $name)).'.'.uniqid(),
            'email' => strtolower(str_replace(' ', '', $name)).'.'.uniqid().'@staff.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        if ($abilities !== []) {
            $user->givePermissionTo($abilities);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        $department = StaffDepartment::firstOrCreate(['name' => 'Operations'], ['is_active' => true]);
        $designation = StaffDesignation::firstOrCreate(['name' => 'Administrator'], ['is_active' => true]);

        StaffProfile::create([
            'user_id' => $user->id,
            'employee_no' => 'EMP-'.$user->id,
            'campus_id' => ($campus ?? $this->school->campus)->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'employment_type' => 'permanent',
            'hire_date' => '2026-04-01',
            'basic_salary' => 50000,
            'allowance_amount' => 0,
            'deduction_amount' => 0,
            'payment_method' => 'bank',
            'is_active' => true,
        ]);

        return $user->fresh();
    }

    public function vehicle(?Campus $campus = null): TransportVehicle
    {
        return TransportVehicle::create([
            'campus_id' => $campus?->id,
            'vehicle_no' => 'VEH-'.uniqid(),
            'vehicle_type' => 'van',
            'capacity' => 20,
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    public function stop(?Campus $campus = null, string $name = 'Main Stop'): TransportStop
    {
        return TransportStop::create([
            'campus_id' => $campus?->id,
            'name' => $name,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<int, TransportStop>  $stops
     */
    public function route(?Campus $campus = null, array $stops = [], ?TransportVehicle $vehicle = null): TransportRoute
    {
        $route = TransportRoute::create([
            'campus_id' => $campus?->id,
            'transport_vehicle_id' => $vehicle?->id,
            'name' => 'Route-'.uniqid(),
            'monthly_fee' => 1000,
            'is_active' => true,
        ]);

        foreach ($stops as $index => $stop) {
            $route->stops()->attach($stop->id, ['sort_order' => $index + 1]);
        }

        return $route->fresh('stops');
    }

    /**
     * An enrolled student, on the given campus.
     */
    public function student(?Campus $campus = null): Student
    {
        $campus ??= $this->school->campus;

        $student = Student::create([
            'user_id' => $this->school->actor->id,
            'registration_no' => 'REG-'.uniqid(),
            'student_code' => 'STU-'.uniqid(),
            'admission_no' => 'ADM-'.uniqid(),
            'dob' => now()->subYears(10)->toDateString(),
            'gender_id' => $this->school->maleGender->id,
            'student_status_id' => $this->school->activeStatus->id,
            'admission_date' => '2026-04-01',
        ]);

        StudentEnrollmentRecord::create([
            'student_id' => $student->id,
            'session_id' => $this->school->session->id,
            'class_id' => $this->school->class->id,
            'section_id' => $this->school->section->id,
            'campus_id' => $campus->id,
            'admission_date' => '2026-04-01',
            'student_status_id' => $this->school->activeStatus->id,
            'monthly_fee' => 0,
            'annual_fee' => 0,
        ]);

        return $student->fresh('currentEnrollment');
    }

    public function assignment(Student $student, TransportRoute $route, ?TransportStop $stop = null, ?Campus $campus = null): TransportStudentAssignment
    {
        return TransportStudentAssignment::create([
            'student_id' => $student->id,
            'student_enrollment_record_id' => $student->currentEnrollment?->id,
            'campus_id' => $campus?->id,
            'transport_route_id' => $route->id,
            'transport_stop_id' => $stop?->id,
            'monthly_fee' => $route->monthly_fee,
            'effective_from' => now()->subDay()->toDateString(),
            'status' => 'active',
            'generate_dues' => true,
        ]);
    }

    private function ensureAbilities(): void
    {
        foreach (self::ABILITIES as $ability) {
            Permission::firstOrCreate(['name' => $ability, 'guard_name' => 'web']);
        }
    }
}
