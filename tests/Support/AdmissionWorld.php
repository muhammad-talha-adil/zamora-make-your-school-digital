<?php

namespace Tests\Support;

use App\Models\Campus;
use App\Models\CampusType;
use App\Models\Fee\DiscountType;
use App\Models\Fee\FeeHead;
use App\Models\Fee\FeeStructure;
use App\Models\Fee\FeeStructureItem;
use App\Models\Gender;
use App\Models\Guardian;
use App\Models\Month;
use App\Models\Relation;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Session as AcademicSession;
use App\Models\StudentStatus;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RolesSeeder;

/**
 * Reference data the admission form needs, plus a valid payload to post.
 *
 * Every admission test needs the same campus / session / class / gender rows
 * before it can say anything interesting, so they are built once here and the
 * ids exposed as public properties. Tests then change only the field under
 * test, which keeps each case about one behaviour.
 */
class AdmissionWorld
{
    public Campus $campus;

    public Campus $otherCampus;

    public AcademicSession $session;

    public AcademicSession $otherSession;

    public SchoolClass $class;

    public SchoolClass $classWithoutSections;

    public Section $section;

    public Section $otherSection;

    public Gender $maleGender;

    public Gender $femaleGender;

    public StudentStatus $activeStatus;

    public Relation $fatherRelation;

    public Relation $motherRelation;

    public FeeHead $monthlyHead;

    public FeeHead $annualHead;

    public FeeHead $optionalHead;

    public User $actor;

    private bool $fullRolesSeeded = false;

    private int $payloadCounter = 0;

    public function __construct()
    {
        $this->seedAcademicStructure();
        $this->seedLookups();
        $this->seedFeeHeads();
        $this->actor = $this->developer();
    }

    public static function make(): self
    {
        return new self;
    }

    /**
     * Seeds the complete role and permission set.
     *
     * Only the authorisation cases need it. Running all 106 permissions and 13
     * roles costs several seconds per test, and every other case is served by
     * the developer shortcut below.
     */
    public function withFullRoles(): self
    {
        if (! $this->fullRolesSeeded) {
            (new PermissionsSeeder)->run();
            (new RolesSeeder)->run();
            $this->fullRolesSeeded = true;
        }

        return $this;
    }

    /**
     * A user holding only the developer role.
     *
     * `Gate::before` grants developers every ability without consulting the
     * permission tables, so this is a valid actor for the cases that are about
     * admission behaviour rather than authorisation.
     */
    private function developer(): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'developer', 'guard_name' => 'web'],
            ['label' => 'Developer', 'scope_level' => Role::SCOPE_SYSTEM, 'is_active' => true]
        );

        $user = User::create([
            'name' => 'Admissions Officer',
            'username' => 'admissions',
            'email' => 'admissions@school.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function seedAcademicStructure(): void
    {
        $type = CampusType::create(['name' => 'Main']);

        $this->campus = Campus::create([
            'name' => 'City Campus',
            'campus_type_id' => $type->id,
            'is_active' => true,
        ]);

        $this->otherCampus = Campus::create([
            'name' => 'Model Town Campus',
            'campus_type_id' => $type->id,
            'is_active' => true,
        ]);

        $this->session = AcademicSession::create([
            'name' => '2025-2026',
            'is_active' => true,
            'start_year' => 2025,
            'end_year' => 2026,
        ]);

        $this->otherSession = AcademicSession::create([
            'name' => '2024-2025',
            'is_active' => false,
            'start_year' => 2024,
            'end_year' => 2025,
        ]);

        $this->class = SchoolClass::create(['name' => 'Class 5', 'code' => 'C5', 'is_active' => true]);
        $this->classWithoutSections = SchoolClass::create(['name' => 'Class 9', 'code' => 'C9', 'is_active' => true]);

        $this->section = Section::create([
            'name' => 'A',
            'code' => 'C5-A',
            'class_id' => $this->class->id,
            'is_active' => true,
        ]);

        $this->otherSection = Section::create([
            'name' => 'B',
            'code' => 'C5-B',
            'class_id' => $this->class->id,
            'is_active' => true,
        ]);
    }

    private function seedLookups(): void
    {
        $this->maleGender = Gender::create(['name' => 'Male']);
        $this->femaleGender = Gender::create(['name' => 'Female']);

        $this->activeStatus = StudentStatus::create(['name' => 'Active']);
        StudentStatus::create(['name' => 'Left']);

        $this->fatherRelation = Relation::create(['name' => 'Father']);
        $this->motherRelation = Relation::create(['name' => 'Mother']);
    }

    /**
     * The codes MONTHLY_TUITION and ANNUAL are the ones the admission
     * repository looks up by name when turning the monthly / annual fee boxes
     * into fee assignments.
     */
    private function seedFeeHeads(): void
    {
        $this->monthlyHead = FeeHead::create([
            'name' => 'Monthly Tuition',
            'code' => 'MONTHLY_TUITION',
            'category' => 'monthly',
            'is_recurring' => true,
            'default_frequency' => 'monthly',
            'is_optional' => false,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->annualHead = FeeHead::create([
            'name' => 'Annual Charges',
            'code' => 'ANNUAL',
            'category' => 'annual',
            'is_recurring' => false,
            'default_frequency' => 'yearly',
            'is_optional' => false,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $this->optionalHead = FeeHead::create([
            'name' => 'Sports',
            'code' => 'SPORTS',
            'category' => 'misc',
            'is_recurring' => false,
            'default_frequency' => 'once',
            'is_optional' => true,
            'sort_order' => 3,
            'is_active' => true,
        ]);
    }

    /**
     * One calendar month row, created on demand.
     *
     * Only fee tests that pin a charge to a month need these, so they are not
     * seeded up front.
     */
    public function month(int $number): Month
    {
        $names = [
            1 => 'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ];

        return Month::firstOrCreate(['month_number' => $number], ['name' => $names[$number]]);
    }

    /**
     * A user holding a role, for authorisation cases.
     */
    public function userWithRole(string $role, string $email): User
    {
        $user = User::create([
            'name' => ucfirst($role),
            'username' => $role.'_'.uniqid(),
            'email' => $email,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $user->syncRoles([$role]);

        return $user;
    }

    /**
     * An existing guardian that an admission can be linked to instead of
     * typing the father's details again.
     */
    public function existingGuardian(string $cnic = '35201-1234567-1', string $phone = '03001234567'): Guardian
    {
        $user = User::create([
            'name' => 'Existing Father',
            'username' => 'guardian_'.uniqid(),
            'email' => 'existing.father.'.uniqid().'@guardian.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        Role::firstOrCreate(
            ['name' => 'guardian', 'guard_name' => 'web'],
            ['label' => 'Guardian', 'scope_level' => Role::SCOPE_SELF, 'is_active' => true]
        );
        $user->syncRoles(['guardian']);

        return Guardian::create([
            'user_id' => $user->id,
            'cnic' => $cnic,
            'phone' => $phone,
            'occupation' => 'Business',
            'address' => 'Lahore',
        ]);
    }

    /**
     * A fee structure with a mandatory monthly head, a mandatory annual head
     * and one optional head.
     *
     * @param  array<string, mixed>  $overrides
     */
    public function feeStructure(array $overrides = []): FeeStructure
    {
        $structure = FeeStructure::create(array_merge([
            'title' => 'Class 5 Standard',
            'campus_id' => $this->campus->id,
            'session_id' => $this->session->id,
            'class_id' => $this->class->id,
            'section_id' => null,
            'is_default' => true,
            'status' => 'active',
            'effective_from' => now()->startOfYear()->toDateString(),
        ], $overrides));

        FeeStructureItem::create([
            'fee_structure_id' => $structure->id,
            'fee_head_id' => $this->monthlyHead->id,
            'amount' => 5000,
            'frequency' => 'monthly',
            'applicable_on_admission' => false,
            'is_optional' => false,
            'is_transport_related' => false,
        ]);

        FeeStructureItem::create([
            'fee_structure_id' => $structure->id,
            'fee_head_id' => $this->annualHead->id,
            'amount' => 12000,
            'frequency' => 'yearly',
            'applicable_on_admission' => true,
            'is_optional' => false,
            'is_transport_related' => false,
        ]);

        FeeStructureItem::create([
            'fee_structure_id' => $structure->id,
            'fee_head_id' => $this->optionalHead->id,
            'amount' => 1500,
            'frequency' => 'once',
            'applicable_on_admission' => false,
            'is_optional' => true,
            'is_transport_related' => false,
        ]);

        return $structure->fresh('items');
    }

    public function discountType(bool $requiresApproval = false): DiscountType
    {
        return DiscountType::create([
            'name' => $requiresApproval ? 'Principal Concession' : 'Sibling Discount',
            'code' => $requiresApproval ? 'PRINCIPAL' : 'SIBLING',
            'value_type' => 'percent',
            'default_value' => 10,
            'is_active' => true,
            'requires_approval' => $requiresApproval,
        ]);
    }

    /**
     * A payload that passes validation.
     *
     * Tests override only the keys they are exercising; passing null for a key
     * removes it, which is how "field is missing" cases are written.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function payload(array $overrides = []): array
    {
        // A guardian's CNIC and phone must be unique, so the defaults vary per
        // call. A test that is about duplicates passes the value it wants.
        $this->payloadCounter++;
        $serial = str_pad((string) $this->payloadCounter, 7, '0', STR_PAD_LEFT);

        $payload = [
            'admission_no' => 'ADM-'.$serial,
            'name' => 'Ahmed Ali',
            'dob' => now()->subYears(10)->toDateString(),
            'gender_id' => $this->maleGender->id,
            'student_status_id' => $this->activeStatus->id,

            'campus_id' => $this->campus->id,
            'session_id' => $this->session->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,

            'father_name' => 'Muhammad Ali',
            'father_relation_id' => $this->fatherRelation->id,
            'father_phone' => '0321'.$serial,
            'father_cnic' => '35202-'.$serial.'-9',
            'father_occupation' => 'Engineer',
            'father_address' => 'Model Town, Lahore',
        ];

        foreach ($overrides as $key => $value) {
            if ($value === null && ! array_key_exists($key, $overrides)) {
                continue;
            }
            $payload[$key] = $value;
        }

        // An explicit null means "leave this field out entirely", which is how
        // required-field cases are expressed.
        return array_filter($payload, fn ($v) => $v !== null);
    }
}
