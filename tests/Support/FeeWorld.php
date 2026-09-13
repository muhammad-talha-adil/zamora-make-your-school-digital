<?php

namespace Tests\Support;

use App\Models\Fee\FeeHead;
use App\Models\Fee\FeeStructure;
use App\Models\Fee\FeeStructureItem;
use App\Models\Fee\FeeVoucher;
use App\Models\Fee\FeeVoucherItem;
use App\Models\Month;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Services\Fee\VoucherGenerationService;
use Illuminate\Database\Eloquent\Collection;

/**
 * A school set up for billing: an enrolled student, a fee structure, and the
 * months of the year.
 *
 * Builds on AdmissionWorld so the academic structure and lookups are shared,
 * and adds only what voucher generation needs.
 */
class FeeWorld
{
    public AdmissionWorld $school;

    public Student $student;

    public StudentEnrollmentRecord $enrollment;

    public FeeStructure $structure;

    public function __construct()
    {
        // Grants the shared actor every `fee.*`/`finance.*` ability —
        // see `AdmissionWorld::grantFeeAbilities()`.
        $this->school = AdmissionWorld::make();

        $this->seedMonths();
        $this->seedSessionDates();
        $this->student = $this->enrolStudent();
        $this->enrollment = $this->student->currentEnrollment;
    }

    public static function make(): self
    {
        return new self;
    }

    /**
     * `HasMonthHelpers` looks months up by number, so all twelve must exist.
     */
    private function seedMonths(): void
    {
        $names = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ];

        foreach ($names as $index => $name) {
            Month::firstOrCreate(['month_number' => $index + 1], ['name' => $name]);
        }
    }

    /**
     * The session starts in April, so April is the "first month" that yearly
     * and one-time charges belong to.
     */
    private function seedSessionDates(): void
    {
        $this->school->session->update([
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
        ]);
    }

    private function enrolStudent(): Student
    {
        $student = Student::create([
            'user_id' => $this->school->actor->id,
            'registration_no' => 'REG-TEST-1',
            'student_code' => 'STU-000001',
            'admission_no' => 'ADM-TEST-1',
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
            'campus_id' => $this->school->campus->id,
            'admission_date' => '2026-04-01',
            'student_status_id' => $this->school->activeStatus->id,
            'monthly_fee' => 0,
            'annual_fee' => 0,
        ]);

        return $student->fresh('currentEnrollment');
    }

    /**
     * A structure with one charge of each frequency.
     *
     * @param  array<string, float>  $amounts
     */
    public function structureWithAllFrequencies(array $amounts = []): FeeStructure
    {
        $this->structure = FeeStructure::create([
            'title' => 'Class 5 Standard',
            'campus_id' => $this->school->campus->id,
            'session_id' => $this->school->session->id,
            'class_id' => $this->school->class->id,
            'section_id' => null,
            'is_default' => true,
            'status' => 'active',
            'effective_from' => '2026-04-01',
            'effective_to' => '2027-03-31',
        ]);

        $this->addItem($this->school->monthlyHead, $amounts['monthly'] ?? 5000, 'monthly');
        $this->addItem($this->school->annualHead, $amounts['yearly'] ?? 12000, 'yearly');
        $this->addItem($this->admissionHead(), $amounts['once'] ?? 20000, 'once');

        return $this->structure->fresh('items');
    }

    public function addItem(FeeHead $head, float $amount, string $frequency): FeeStructureItem
    {
        return FeeStructureItem::create([
            'fee_structure_id' => $this->structure->id,
            'fee_head_id' => $head->id,
            'amount' => $amount,
            'frequency' => $frequency,
            'applicable_on_admission' => false,
            'is_optional' => false,
            'is_transport_related' => false,
        ]);
    }

    /**
     * A head charged once in a student's life, such as the admission fee.
     */
    public function admissionHead(): FeeHead
    {
        return FeeHead::firstOrCreate(
            ['code' => 'ADMISSION'],
            [
                'name' => 'Admission Fee',
                'category' => 'one_time',
                'is_recurring' => false,
                'default_frequency' => 'once',
                'is_optional' => false,
                'sort_order' => 4,
                'is_active' => true,
            ]
        );
    }

    public function monthId(int $monthNumber): int
    {
        return Month::where('month_number', $monthNumber)->value('id');
    }

    /**
     * Generates one month's vouchers and returns the student's lines.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, FeeVoucherItem>
     */
    public function generate(int $month, int $year = 2026, array $filters = [])
    {
        app(VoucherGenerationService::class)->generateMonthlyVouchers($month, $year, $filters);

        return FeeVoucherItem::where('fee_voucher_id', $this->voucherFor($month, $year)->id)->get();
    }

    public function voucherFor(int $month, int $year = 2026): FeeVoucher
    {
        return FeeVoucher::where('voucher_year', $year)
            ->where('voucher_month_id', $this->monthId($month))
            ->latest('id')
            ->firstOrFail();
    }
}
