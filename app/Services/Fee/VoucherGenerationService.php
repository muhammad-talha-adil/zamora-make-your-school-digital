<?php

namespace App\Services\Fee;

use App\Enums\Fee\AssignmentType;
use App\Enums\Fee\FeeFrequency;
use App\Enums\Fee\ValueType;
use App\Enums\Fee\VoucherItemSource;
use App\Enums\Fee\VoucherStatus;
use App\Models\Fee\FeeFineRule;
use App\Models\Fee\FeeHead;
use App\Models\Fee\FeePolicy;
use App\Models\Fee\FeeStructure;
use App\Models\Fee\FeeStructureItem;
use App\Models\Fee\FeeVoucher;
use App\Models\Fee\FeeVoucherItem;
use App\Models\Fee\StudentPaidOneTimeFee;
use App\Models\Month;
use App\Models\StudentEnrollmentRecord;
use App\Models\StudentLateArrivalFine;
use App\Services\Attendance\LateArrivalFineService;
use App\Services\Finance\StudentBillingService;
use App\Services\Finance\UnifiedAccountingService;
use App\Traits\Fee\HasMonthHelpers;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VoucherGenerationService
{
    use HasMonthHelpers;

    public function __construct(
        protected StudentBillingService $studentBillingService,
        protected UnifiedAccountingService $accountingService,
        protected SiblingDiscountService $siblingDiscounts,
        protected LateArrivalFineService $lateArrivalFines
    ) {}

    /**
     * Generate monthly vouchers for students
     */
    public function generateMonthlyVouchers(int $monthNumber, int $year, array $filters = []): array
    {
        // Sibling ranks are worked out once per family and held for the run; a
        // second run must see any admission made since the first.
        $this->siblingDiscounts->forget();

        return DB::transaction(function () use ($monthNumber, $year, $filters) {
            $monthId = self::getMonthIdByNumber($monthNumber);

            if (! $monthId) {
                throw new \Exception('Invalid month number');
            }

            $query = StudentEnrollmentRecord::active()
                ->with(['student', 'session', 'campus', 'class', 'section', 'feeStructure', 'feeStructure.items']);

            // Apply filters - session is required now
            if (! empty($filters['session_id'])) {
                $query->where('session_id', $filters['session_id']);
            }
            if (! empty($filters['campus_id'])) {
                $query->where('campus_id', $filters['campus_id']);
            }
            if (! empty($filters['class_id'])) {
                $query->where('class_id', $filters['class_id']);
            }
            if (! empty($filters['section_id'])) {
                $query->where('section_id', $filters['section_id']);
            }

            $enrollments = $query->get();
            $generated = 0;
            $skipped = 0;
            $errors = [];

            // Get custom fee heads for this generation
            $customFeeHeads = $filters['custom_fee_heads'] ?? [];
            $includePreviousUnpaid = $filters['include_previous_unpaid'] ?? false;
            $includeInventoryDues = $filters['include_inventory_dues'] ?? false;
            $includeTransportDues = $filters['include_transport_dues'] ?? false;

            foreach ($enrollments as $enrollment) {
                try {
                    // Check if voucher already exists
                    $exists = FeeVoucher::where('student_id', $enrollment->student_id)
                        ->where('voucher_month_id', $monthId)
                        ->where('voucher_year', $year)
                        ->exists();

                    if ($exists) {
                        $skipped++;

                        continue;
                    }

                    $this->generateVoucherForStudent(
                        $enrollment,
                        $monthId,
                        $monthNumber,
                        $year,
                        $customFeeHeads,
                        $includePreviousUnpaid,
                        $includeInventoryDues,
                        $includeTransportDues
                    );
                    $generated++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'student_id' => $enrollment->student_id,
                        'error' => $e->getMessage(),
                    ];
                    Log::error('Voucher generation failed for student', [
                        'student_id' => $enrollment->student_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return [
                'generated' => $generated,
                'skipped' => $skipped,
                'errors' => $errors,
                'total' => $enrollments->count(),
            ];
        });
    }

    /**
     * Generate voucher for a single student
     *
     * @throws \Exception
     */
    protected function generateVoucherForStudent(
        StudentEnrollmentRecord $enrollment,
        int $monthId,
        int $monthNumber,
        int $year,
        array $customFeeHeads = [],
        bool $includePreviousUnpaid = false,
        bool $includeInventoryDues = false,
        bool $includeTransportDues = false
    ): FeeVoucher {
        // Validate enrollment is still active
        if (! $enrollment->isActive()) {
            throw new \Exception('Student enrollment is not active');
        }

        // Validate enrollment has a leave date in the future (or null)
        if ($enrollment->leave_date && $enrollment->leave_date->lt(now()->toDateString())) {
            throw new \Exception('Student has left the institution');
        }

        // Get applicable fee structure
        $structure = $this->getApplicableFeeStructure($enrollment);

        // Skip students without a fee structure (neither directly assigned nor available for class/campus)
        if (! $structure) {
            throw new \Exception('No fee structure found for this student');
        }

        // Generate voucher number
        $voucherNo = $this->generateVoucherNumber($year, $enrollment->campus_id);

        // Create voucher
        $voucher = FeeVoucher::create([
            'voucher_no' => $voucherNo,
            'student_id' => $enrollment->student_id,
            'student_enrollment_record_id' => $enrollment->id,
            'session_id' => $enrollment->session_id,
            'campus_id' => $enrollment->campus_id,
            'class_id' => $enrollment->class_id,
            'section_id' => $enrollment->section_id,
            'voucher_month_id' => $monthId,
            'voucher_year' => $year,
            'issue_date' => now(),
            'due_date' => now()->addDays(15),
            'status' => VoucherStatus::UNPAID,
            'generated_by' => auth()->id(),
        ]);

        // Add items - either from fee structure or from enrollment/custom assignments
        $this->addVoucherItems($voucher, $structure, $enrollment, $monthNumber, $customFeeHeads);

        if ($includeInventoryDues) {
            $this->attachOpenInventoryCharges($voucher, $enrollment);
        }

        if ($includeTransportDues) {
            $this->attachOpenTransportCharges($voucher, $enrollment);
        }

        $this->attachLateArrivalFines($voucher, $enrollment, $monthNumber, $year);

        // Include previous unpaid balance if requested
        $previousVoucherIds = [];
        if ($includePreviousUnpaid) {
            $previousVoucherIds = $this->addPreviousUnpaidBalance($voucher, $enrollment, $monthNumber, $year);
        }

        // Store previous voucher IDs if any
        if (! empty($previousVoucherIds)) {
            $voucher->update(['previous_voucher_ids' => $previousVoucherIds]);
        }

        // Calculate totals
        $this->calculateVoucherTotals($voucher);

        // Post accounting entry for the new voucher-backed charges.
        $this->accountingService->postVoucherJournal($voucher);

        return $voucher;
    }

    /**
     * Add previous unpaid balance to the voucher
     * Returns array of previous voucher IDs
     */
    protected function addPreviousUnpaidBalance(
        FeeVoucher $voucher,
        StudentEnrollmentRecord $enrollment,
        int $currentMonthNumber,
        int $currentYear
    ): array {
        // Get the month ID for the current month number
        $currentMonthId = self::getMonthIdByNumber($currentMonthNumber);

        if (! $currentMonthId) {
            return [];
        }

        // Get all unpaid vouchers for this student before the current month/year
        $previousVouchers = FeeVoucher::where('student_id', $enrollment->student_id)
            ->where(function ($query) use ($currentMonthId, $currentYear) {
                $query->where('voucher_year', '<', $currentYear)
                    ->orWhere(function ($q) use ($currentMonthId, $currentYear) {
                        $q->where('voucher_year', '=', $currentYear)
                            ->where('voucher_month_id', '<', $currentMonthId);
                    });
            })
            ->whereIn('status', [VoucherStatus::UNPAID, VoucherStatus::PARTIAL])
            ->get();

        if ($previousVouchers->isEmpty()) {
            return [];
        }

        // Get previous voucher IDs
        $previousVoucherIds = $previousVouchers->pluck('id')->toArray();

        // Calculate total previous balance
        $totalPreviousBalance = $previousVouchers->sum('balance_amount');

        if ($totalPreviousBalance > 0) {
            // Seeded by FeeHeadSeeder. It used to be created here on the fly
            // with category 'other', which the FeeHeadCategory enum does not
            // have, so the first student to fall behind killed generation.
            $previousBalanceHead = FeeHead::where('code', 'PREVIOUS_BALANCE')->first();

            if (! $previousBalanceHead) {
                throw new \RuntimeException(
                    'The PREVIOUS_BALANCE fee head is missing; run the fee head seeder.'
                );
            }

            $this->createBilledVoucherCharge(
                $voucher,
                $enrollment,
                $previousBalanceHead,
                'Previous Outstanding Balance (Arrears)',
                (float) $totalPreviousBalance,
                0,
                VoucherItemSource::ARREARS,
                'previous_voucher_balance',
                null,
                null,
                'arrears',
                false,
                [
                    'previous_voucher_ids' => $previousVoucherIds,
                ]
            );

            // Fines are worked out before the old vouchers are closed, while
            // their balance and due date still describe what was outstanding.
            $this->addLateFines($voucher, $enrollment, $previousVouchers);

            $this->supersedeCarriedVouchers($previousVouchers, $voucher);
        }

        return $previousVoucherIds;
    }

    /**
     * Charges a late fine on each overdue voucher being carried forward.
     *
     * `fee_fine_rules` and `FeeFineRule::calculateFine()` already existed but
     * nothing ever called them, so a school could define "Rs 50 a day after 5
     * grace days" and never collect a rupee of it.
     *
     * The fine is charged once, on the voucher that carries the arrears, using
     * the days between the old voucher's due date and this one's issue date.
     *
     * @param  Collection<int, FeeVoucher>  $overdue
     */
    protected function addLateFines(
        FeeVoucher $voucher,
        StudentEnrollmentRecord $enrollment,
        $overdue
    ): void {
        $rule = $this->applicableFineRule($enrollment, $voucher->issue_date);

        if (! $rule) {
            return;
        }

        $fineHead = FeeHead::where('code', 'LATE_FINE')->first();

        if (! $fineHead) {
            Log::warning('Late fine rule found but no LATE_FINE fee head is seeded', [
                'rule_id' => $rule->id,
            ]);

            return;
        }

        foreach ($overdue as $old) {
            if (! $old->due_date || (float) $old->balance_amount <= 0) {
                continue;
            }

            $daysLate = $old->due_date->diffInDays($voucher->issue_date, absolute: false);

            if ($daysLate <= 0) {
                continue;
            }

            $fine = $rule->calculateFine((float) $old->balance_amount, (int) $daysLate);

            if ($fine <= 0) {
                continue;
            }

            $this->createBilledVoucherCharge(
                $voucher,
                $enrollment,
                $fineHead,
                'Late Fee - voucher '.$old->voucher_no.' ('.$daysLate.' days late)',
                $fine,
                0,
                VoucherItemSource::ARREARS,
                'late_fine',
                $old->id,
                $rule->id,
                'fine',
                false,
                [
                    'fine_rule_id' => $rule->id,
                    'fine_type' => $rule->fine_type?->value,
                    'days_late' => $daysLate,
                    'grace_days' => $rule->grace_days,
                    'overdue_voucher_no' => $old->voucher_no,
                ]
            );
        }
    }

    /**
     * The fine rule that applies to this enrollment.
     *
     * Rules can be scoped to a campus, session, class or section; the most
     * specific match wins so a class-level rule overrides a campus-wide one.
     */
    protected function applicableFineRule(StudentEnrollmentRecord $enrollment, $onDate): ?FeeFineRule
    {
        return FeeFineRule::active()
            ->effectiveOn($onDate)
            // Campus and session are required on a rule; class and section are
            // optional, and leaving them null makes the rule campus-wide.
            ->where('campus_id', $enrollment->campus_id)
            ->where('session_id', $enrollment->session_id)
            ->where(fn ($q) => $q->whereNull('class_id')->orWhere('class_id', $enrollment->class_id))
            ->where(fn ($q) => $q->whereNull('section_id')->orWhere('section_id', $enrollment->section_id))
            ->orderByRaw('(section_id IS NOT NULL) DESC')
            ->orderByRaw('(class_id IS NOT NULL) DESC')
            ->first();
    }

    /**
     * Closes vouchers whose balance has moved onto a newer one.
     *
     * The carried balance is now billed on the new voucher, so leaving the old
     * ones `unpaid` counted the same arrears twice -- once on each -- and
     * overstated both the defaulter list and the receivable ledger.
     *
     * @param  Collection<int, FeeVoucher>  $carried
     */
    protected function supersedeCarriedVouchers($carried, FeeVoucher $absorbedBy): void
    {
        foreach ($carried as $old) {
            $old->update([
                'status' => VoucherStatus::ADJUSTED,
                'balance_amount' => 0,
                'notes' => trim(($old->notes ?? '')."\nBalance carried forward to voucher {$absorbedBy->voucher_no}."),
            ]);
        }

        Log::info('Previous vouchers superseded by carry-forward', [
            'absorbed_by' => $absorbedBy->id,
            'superseded' => $carried->pluck('id')->all(),
        ]);
    }

    /**
     * Get applicable fee structure for enrollment
     * Priority:
     * 1. Direct assignment on enrollment record (fee_structure_id)
     * 2. Section-specific fee structure
     * 3. Class-specific fee structure
     * 4. Campus-wide fee structure
     */
    protected function getApplicableFeeStructure(StudentEnrollmentRecord $enrollment): ?FeeStructure
    {
        $baseQuery = FeeStructure::active()
            ->with('items')
            ->where('session_id', $enrollment->session_id)
            ->where('campus_id', $enrollment->campus_id)
            ->orderByDesc('is_default')
            ->orderByDesc('effective_from')
            ->orderByDesc('id');

        // 1. First check if there's a direct fee structure assigned to the enrollment
        // Use the preloaded relationship if available
        if ($enrollment->fee_structure_id && $enrollment->relationLoaded('feeStructure')) {
            $structure = $enrollment->feeStructure;
            if ($structure && $structure->status?->value === 'active') {
                return $structure;
            }
        }

        // Match the enrollment/session scope directly rather than blocking on today's date.
        if ($enrollment->section_id) {
            $structure = (clone $baseQuery)
                ->where('class_id', $enrollment->class_id)
                ->where('section_id', $enrollment->section_id)
                ->first();

            if ($structure) {
                return $structure;
            }
        }

        $structure = (clone $baseQuery)
            ->where('class_id', $enrollment->class_id)
            ->whereNull('section_id')
            ->first();

        if ($structure) {
            return $structure;
        }

        return (clone $baseQuery)
            ->whereNull('class_id')
            ->whereNull('section_id')
            ->first();
    }

    /**
     * Generate unique voucher number with race condition protection.
     * Uses DB lock to prevent concurrent generation.
     */
    protected function generateVoucherNumber(int $year, int $campusId): string
    {
        return DB::transaction(function () use ($year) {
            $lastVoucher = FeeVoucher::where('voucher_year', $year)
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            $nextNumber = $lastVoucher ? ((int) substr($lastVoucher->voucher_no, -6)) + 1 : 1;

            return 'FV-'.$year.'-'.str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Add voucher items from fee structure OR from enrollment/custom assignments
     */
    protected function addVoucherItems(
        FeeVoucher $voucher,
        ?FeeStructure $structure,
        StudentEnrollmentRecord $enrollment,
        int $monthNumber,
        array $customFeeHeads = []
    ): void {
        /*
         * What the office agreed at admission, per fee head.
         *
         * The enrollment records the intent in `fee_mode`, so a typed amount is
         * only treated as an override when the office actually chose manual
         * mode. This used to be hardcoded to an empty array, which meant a
         * negotiated fee was stored at admission and then never billed.
         */
        $customAssignments = $this->getAgreedAmounts($enrollment);

        // Fee heads already settled once and for all. Only `once` charges are
        // checked against this; a monthly or yearly head is billed on its own
        // schedule however many times it has been paid before.
        $paidOnceFeeHeadIds = $this->getPaidOneTimeFeeHeadIds($enrollment);

        // Check if this is the first voucher of the session for one-time fees
        $firstVoucherOfSession = ! $this->hasExistingVouchers($enrollment, $voucher);

        // If we have a fee structure, use it
        if ($structure && $structure->items->isNotEmpty()) {
            // Check if this is the first month of session for annual fees
            $sessionStartMonth = $enrollment->session->start_date ?? now();
            $firstMonthOfSession = (int) date('n', strtotime($sessionStartMonth));
            $isFirstMonth = $firstMonthOfSession == $monthNumber;

            foreach ($structure->items as $item) {
                // `frequency` is cast to FeeFrequency, so it is compared as an
                // enum. Comparing it against a string is always false, which
                // billed yearly and one-time charges every single month.
                $frequency = $item->frequency;

                // A yearly charge split into instalments is billed in each of
                // its instalment months instead of landing whole on the first
                // month of the session, where it used to sit on top of that
                // month's tuition and hand the parent a bill they could not pay.
                $instalment = $frequency === FeeFrequency::YEARLY && $item->isInstalled()
                    ? $item->instalmentAmountForMonth($monthNumber)
                    : null;

                // Check frequency - skip if yearly and not first month
                if ($frequency === FeeFrequency::YEARLY && $instalment === null && ! $isFirstMonth) {
                    continue;
                }

                // An instalment charge is billed only in its own months, never
                // in the session's first month by default.
                if ($frequency === FeeFrequency::YEARLY && $item->isInstalled() && $instalment === null) {
                    continue;
                }

                if ($frequency === FeeFrequency::ONCE) {
                    // Only add if this is the first voucher of the session
                    if (! $firstVoucherOfSession) {
                        continue;
                    }
                    // Also check if already paid (for readmitted students)
                    if (in_array($item->fee_head_id, $paidOnceFeeHeadIds, true)) {
                        Log::info('Skipping one-time fee head - already settled', [
                            'student_id' => $enrollment->student_id,
                            'fee_head_id' => $item->fee_head_id,
                            'voucher_month' => $monthNumber,
                        ]);

                        continue;
                    }
                }

                // Check if item is applicable for this month based on month range
                if ($frequency !== FeeFrequency::ONCE && ! $item->isApplicableForMonth($monthNumber)) {
                    continue;
                }

                $amount = $item->amount;
                // Check if student has custom assignment for this fee head
                $customAmount = $this->getCustomAmountForFeeHead($customAssignments, $item->fee_head_id);
                if ($customAmount !== null) {
                    $amount = $customAmount;
                }

                $isOnce = $frequency === FeeFrequency::ONCE;
                $instalmentLabel = '';

                if ($instalment !== null) {
                    // The agreed figure is still the year's charge, so the
                    // instalment is taken as a share of whatever is being billed
                    // rather than of the structure's own amount.
                    $share = (float) $item->amount > 0
                        ? $instalment / (float) $item->amount
                        : 0.0;

                    $amount = round((float) $amount * $share, 2);
                    $instalmentLabel = $this->instalmentLabelFor($item, $monthNumber);
                }

                // A child admitted part-way through the month owes only part of
                // that month's recurring charge, where the campus says so.
                $amount = $this->prorated($enrollment, $frequency, $monthNumber, $voucher->voucher_year, (float) $amount);

                $discount = $this->largestDiscountFor(
                    $enrollment->student,
                    $item->fee_head_id,
                    (float) $amount,
                    $enrollment->id,
                    $this->billedMonthOf($voucher, $monthNumber),
                    $enrollment,
                );

                $description = $item->feeHead->name.($isOnce ? ' (One Time)' : ' - '.self::getMonthNameByNumber($monthNumber).' '.$voucher->voucher_year).$instalmentLabel;
                $this->createBilledVoucherCharge(
                    $voucher,
                    $enrollment,
                    $item->feeHead,
                    $description,
                    $amount,
                    $discount,
                    VoucherItemSource::STRUCTURE,
                    'fee_structure_item',
                    $item->id,
                    null,
                    ($item->feeHead->category?->value) ?? 'fee',
                    ! $isOnce,
                    [
                        'frequency' => $frequency?->value,
                        'fee_structure_id' => $structure?->id,
                    ]
                );

                // Record one-time fee payment if applicable
                if ($isOnce && ($amount - $discount) > 0) {
                    StudentPaidOneTimeFee::recordPayment(
                        $enrollment->student_id,
                        $item->fee_head_id,
                        $amount - $discount,
                        $voucher->id,
                        'Automatically recorded during voucher generation'
                    );
                }
            }
        } else {
            // No fee structure found - use enrollment fees and custom assignments
            $this->addVoucherItemsFromEnrollment($voucher, $enrollment, $monthNumber, $customAssignments, $paidOnceFeeHeadIds);
        }

        // Add custom fee heads that were provided during generation (skip if already paid)
        $this->addCustomFeeHeads($voucher, $customFeeHeads, $monthNumber, $paidOnceFeeHeadIds);
    }

    /**
     * Fee heads the student has already settled as a one-off.
     *
     * Only `once` charges belong here. This used to gather every fee head from
     * every paid voucher in the session, which meant that once a student paid
     * January's tuition the monthly head counted as "paid" and was never billed
     * again for the rest of the year.
     *
     * @return list<int>
     */
    protected function getPaidOneTimeFeeHeadIds(StudentEnrollmentRecord $enrollment): array
    {
        return StudentPaidOneTimeFee::where('student_id', $enrollment->student_id)
            // A refunded charge is no longer settled: a child who left, took the
            // admission fee back and later returned is admitted afresh.
            ->notRefunded()
            ->pluck('fee_head_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Add custom fee heads that were specified during voucher generation
     * Skips fee heads that have already been paid
     */
    protected function addCustomFeeHeads(FeeVoucher $voucher, array $customFeeHeads, int $monthNumber, array $paidOnceFeeHeadIds = []): void
    {
        if (empty($customFeeHeads)) {
            return;
        }

        $month = Month::find($voucher->voucher_month_id);
        $monthName = $month ? $month->name : self::getMonthNameByNumber($monthNumber);

        foreach ($customFeeHeads as $customFee) {
            // Skip if this fee head was already paid
            if (in_array((int) $customFee['fee_head_id'], $paidOnceFeeHeadIds, true)) {
                Log::info('Skipping custom fee head - already paid', [
                    'student_id' => $voucher->student_id,
                    'fee_head_id' => $customFee['fee_head_id'],
                ]);

                continue;
            }

            $feeHead = FeeHead::find($customFee['fee_head_id']);

            if (! $feeHead) {
                continue;
            }

            $this->createBilledVoucherCharge(
                $voucher,
                $voucher->enrollmentRecord,
                $feeHead,
                $feeHead->name.' - '.$monthName.' '.$voucher->voucher_year,
                (float) $customFee['amount'],
                0,
                VoucherItemSource::MANUAL,
                'manual_fee_head',
                null,
                null,
                ($feeHead->category?->value) ?? 'fee',
                false,
                [
                    'custom_fee_head' => true,
                ]
            );
        }
    }

    /**
     * Add voucher items from enrollment record when no fee structure exists
     * This ensures vouchers can be generated even without a fee structure
     */
    protected function addVoucherItemsFromEnrollment(
        FeeVoucher $voucher,
        StudentEnrollmentRecord $enrollment,
        int $monthNumber,
        array $customAssignments,
        array $paidOnceFeeHeadIds = []
    ): void {
        // Get or create Monthly and Annual fee heads
        $monthlyFeeHead = FeeHead::where('code', 'MONTHLY_TUITION')->first();
        $annualFeeHead = FeeHead::where('code', 'ANNUAL')->first();

        // Check if this is the first month of session for annual fee
        $sessionStartMonth = $enrollment->session->start_date ?? now();
        $isFirstMonth = date('n', strtotime($sessionStartMonth)) == $monthNumber;

        // Monthly tuition recurs, so a payment in an earlier month must not
        // stop it being billed in this one.
        if ($monthlyFeeHead) {
            // Check for custom amount first, then fall back to enrollment
            $customMonthlyAmount = $this->getCustomAmountForFeeHead($customAssignments, $monthlyFeeHead->id);
            // Prorated for a child admitted part-way through this month, where
            // the campus policy says so.
            $monthlyAmount = $this->prorated(
                $enrollment,
                FeeFrequency::MONTHLY,
                $monthNumber,
                (int) $voucher->voucher_year,
                (float) ($customMonthlyAmount ?? $enrollment->monthly_fee ?? 0)
            );

            if ($monthlyAmount > 0) {
                $discount = $this->largestDiscountFor(
                    $enrollment->student,
                    $monthlyFeeHead->id,
                    (float) $monthlyAmount,
                    $enrollment->id,
                    $this->billedMonthOf($voucher, $monthNumber),
                    $enrollment,
                );

                $this->createBilledVoucherCharge(
                    $voucher,
                    $enrollment,
                    $monthlyFeeHead,
                    $monthlyFeeHead->name.' - '.self::getMonthNameByNumber($monthNumber).' '.$voucher->voucher_year,
                    (float) $monthlyAmount,
                    (float) $discount,
                    VoucherItemSource::STRUCTURE,
                    'enrollment_monthly_fee',
                    $enrollment->id,
                    null,
                    ($monthlyFeeHead->category?->value) ?? 'fee',
                    true,
                    [
                        'fallback_source' => 'enrollment_monthly_fee',
                    ]
                );
            }
        }

        // Add annual fee (only in first month of session and if not already paid)
        if ($annualFeeHead && $isFirstMonth) {
            $customAnnualAmount = $this->getCustomAmountForFeeHead($customAssignments, $annualFeeHead->id);
            $annualAmount = $customAnnualAmount ?? $enrollment->annual_fee ?? 0;

            if ($annualAmount > 0) {
                $discount = $this->largestDiscountFor(
                    $enrollment->student,
                    $annualFeeHead->id,
                    (float) $annualAmount,
                    $enrollment->id,
                    $this->billedMonthOf($voucher, $monthNumber),
                    $enrollment,
                );

                $this->createBilledVoucherCharge(
                    $voucher,
                    $enrollment,
                    $annualFeeHead,
                    $annualFeeHead->name.' - '.$voucher->voucher_year,
                    (float) $annualAmount,
                    (float) $discount,
                    VoucherItemSource::STRUCTURE,
                    'enrollment_annual_fee',
                    $enrollment->id,
                    null,
                    ($annualFeeHead->category?->value) ?? 'fee',
                    false,
                    [
                        'fallback_source' => 'enrollment_annual_fee',
                    ]
                );
            }
        }

        Log::info('Voucher items added from enrollment (no fee structure found)', [
            'voucher_id' => $voucher->id,
            'student_id' => $enrollment->student_id,
            'monthly_fee' => $enrollment->monthly_fee,
            'annual_fee' => $enrollment->annual_fee,
            'custom_assignments' => count($customAssignments),
            'paid_one_time_fee_head_ids' => $paidOnceFeeHeadIds,
        ]);
    }

    /**
     * The amounts this enrollment is billed on, keyed by fee head.
     *
     * `fee_mode` says how the office set the fee up at admission:
     *
     *   structure  the structure's own amounts; nothing is overridden
     *   discount   the structure's amounts, less the student's discounts
     *   manual     the amounts typed on the admission form
     *
     * Only `manual` produces overrides. A null mode is an older record and is
     * treated as `structure`. Mid-session changes come from
     * `student_fee_assignments` and are merged on top, since they are the more
     * recent decision.
     *
     * @return array<int, array{amount: float}>
     */
    protected function getAgreedAmounts(StudentEnrollmentRecord $enrollment): array
    {
        $amounts = [];

        $mode = $enrollment->fee_mode instanceof \BackedEnum
            ? $enrollment->fee_mode->value
            : $enrollment->fee_mode;

        if ($mode === 'manual') {
            foreach ($enrollment->custom_fee_entries ?? [] as $entry) {
                if (! isset($entry['fee_head_id'])) {
                    continue;
                }

                $amounts[(int) $entry['fee_head_id']] = ['amount' => (float) ($entry['amount'] ?? 0)];
            }
        }

        foreach ($this->getStudentFeeAssignments($enrollment) as $feeHeadId => $assignment) {
            $amounts[(int) $feeHeadId] = ['amount' => (float) $assignment['amount']];
        }

        return $amounts;
    }

    /**
     * Mid-session fee overrides recorded against the student.
     *
     * These are the deliberate changes made after admission -- a revised fee,
     * a waiver -- not the amounts agreed on the admission form.
     */
    protected function getStudentFeeAssignments(StudentEnrollmentRecord $enrollment): array
    {
        $assignments = $enrollment->student
            ->feeAssignments()
            ->where('is_active', true)
            ->where('assignment_type', AssignmentType::OVERRIDE)
            ->where(function ($query) {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', now()->toDateString());
            })
            ->where(function ($query) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', now()->toDateString());
            })
            ->get();

        return $assignments->keyBy('fee_head_id')->toArray();
    }

    /**
     * Get custom amount for a specific fee head from assignments
     */
    protected function getCustomAmountForFeeHead(array $assignments, int $feeHeadId): ?float
    {
        if (isset($assignments[$feeHeadId])) {
            $assignment = $assignments[$feeHeadId];

            return (float) $assignment['amount'];
        }

        return null;
    }

    /**
     * What a child admitted part-way through the month owes for that month.
     *
     * Only the month the child was admitted in is affected, and only recurring
     * charges: a yearly or one-time charge is not a smaller thing because the
     * child arrived late. Campuses that charge the full month regardless — the
     * default — get their amount back untouched.
     */
    protected function prorated(
        StudentEnrollmentRecord $enrollment,
        ?FeeFrequency $frequency,
        int $monthNumber,
        int $year,
        float $amount
    ): float {
        if ($frequency !== FeeFrequency::MONTHLY || ! $enrollment->admission_date) {
            return $amount;
        }

        $admittedOn = Carbon::parse($enrollment->admission_date);

        if ((int) $admittedOn->month !== $monthNumber || (int) $admittedOn->year !== $year) {
            return $amount;
        }

        $policy = FeePolicy::resolve($enrollment->campus_id, $enrollment->session_id);

        if (! $policy->prorates()) {
            return $amount;
        }

        return round($amount * $policy->shareOfMonthFor($admittedOn), 2);
    }

    /**
     * "(Instalment 2 of 3)", for a charge collected in parts.
     */
    protected function instalmentLabelFor(FeeStructureItem $item, int $monthNumber): string
    {
        $months = $item->instalmentMonthNumbers();
        $position = array_search($monthNumber, $months, true);

        if ($position === false) {
            return '';
        }

        return ' (Instalment '.($position + 1).' of '.count($months).')';
    }

    /**
     * Bills whatever the attendance module worked out for late arrival.
     *
     * The two modules meet at `student_late_arrival_fines` and nowhere else:
     * attendance knows who was late and how often, this knows how to put a
     * charge on a voucher. Nothing happens at all unless the campus has turned
     * the policy on, which most schools here will not.
     *
     * Each charge is marked billed as it goes on, so a re-run cannot charge the
     * same month twice, and it names its own working — "six late arrivals,
     * three forgiven, three charged" — because a figure a parent cannot have
     * explained to them is a figure that gets argued about at the counter.
     */
    protected function attachLateArrivalFines(
        FeeVoucher $voucher,
        StudentEnrollmentRecord $enrollment,
        int $monthNumber,
        int $year
    ): void {
        $fines = $this->lateArrivalFines->billableFor(
            (int) $enrollment->student_id,
            (int) $enrollment->session_id,
            Carbon::create($year, $monthNumber, 1)
        );

        if ($fines->isEmpty()) {
            return;
        }

        $fineHead = FeeHead::where('code', 'LATE_FINE')->first();

        if (! $fineHead) {
            Log::warning('Late arrival fines are due but no LATE_FINE fee head is seeded', [
                'student_id' => $enrollment->student_id,
            ]);

            return;
        }

        foreach ($fines as $fine) {
            $item = $this->createBilledVoucherCharge(
                $voucher,
                $enrollment,
                $fineHead,
                'Late Arrival Fine - '.self::getMonthNameByNumber($fine->month).' '.$fine->year
                    .' ('.$fine->explanation().')',
                (float) $fine->amount,
                0,
                VoucherItemSource::MANUAL,
                'late_arrival_fine',
                $fine->id,
                null,
                'fine',
                false,
                ['late_count' => $fine->late_count, 'charged_count' => $fine->charged_count]
            );

            $fine->update([
                'status' => StudentLateArrivalFine::STATUS_BILLED,
                'fee_voucher_item_id' => $item?->id,
            ]);
        }
    }

    /**
     * The month a voucher bills, which is not the month it is printed in.
     *
     * Back-billing an earlier month, or issuing next month's voucher early, must
     * not change which concessions and rules the charge is judged against.
     */
    protected function billedMonthOf(FeeVoucher $voucher, int $monthNumber): Carbon
    {
        return Carbon::create((int) $voucher->voucher_year, $monthNumber, 1)->startOfDay();
    }

    /**
     * The discount that applies to one charge.
     *
     * A student can hold several concessions at once — sibling, staff child,
     * merit. Only the largest applies to any one fee head:
     *
     *  - Adding them up would go past 100%. The seeded types alone include
     *    MERIT at 100% and STAFF at 50%, so a staff child on merit would have
     *    the school paying them.
     *  - They are compared in rupees, not in percent, because 10% of a
     *    Rs 12,000 charge beats a flat Rs 500 while looking smaller.
     *  - The result is capped at the charge, so a voucher line never goes
     *    negative.
     *
     * Discounts on different fee heads do not compete; each head is settled on
     * its own.
     *
     * @param  Carbon|string|null  $billedMonth  any date inside the month being billed
     */
    protected function largestDiscountFor(
        $student,
        int $feeHeadId,
        float $amount,
        ?int $enrollmentId = null,
        $billedMonth = null,
        ?StudentEnrollmentRecord $enrollment = null
    ): float {
        // Matched against the month being billed, not the day the voucher is
        // printed: generating March's voucher in June used to check whether the
        // concession was still valid in June and quietly drop it from March.
        $monthStart = ($billedMonth ? Carbon::parse($billedMonth) : now())->copy()->startOfMonth();

        $query = $student->discounts()
            ->approved()
            ->effectiveDuring($monthStart->toDateString(), $monthStart->copy()->endOfMonth()->toDateString())
            ->where(function ($q) use ($feeHeadId) {
                $q->whereNull('fee_head_id')
                    ->orWhere('fee_head_id', $feeHeadId);
            });

        if ($enrollmentId) {
            $query->where('student_enrollment_record_id', $enrollmentId);
        }

        $best = $query->get()
            ->map(fn ($discount) => $discount->value_type === ValueType::PERCENT
                ? ($amount * (float) $discount->value) / 100
                : (float) $discount->value)
            ->max() ?? 0.0;

        // The automatic sibling concession competes with the entered ones under
        // the same rule rather than stacking on them, so a staff child who is
        // also a second child gets the better of the two, not both.
        if ($enrollment) {
            $best = max($best, $this->siblingDiscounts->amountOff($enrollment, $feeHeadId, $amount));
        }

        return (float) min($best, $amount);
    }

    /**
     * Calculate voucher totals
     */
    protected function calculateVoucherTotals(FeeVoucher $voucher): void
    {
        $items = $voucher->items;

        $grossAmount = $items->sum('amount');
        $discountAmount = $items->sum('discount_amount');
        $netAmount = $items->sum('net_amount');

        $voucher->update([
            'gross_amount' => $grossAmount,
            'discount_amount' => $discountAmount,
            'net_amount' => $netAmount,
            'balance_amount' => $netAmount,
        ]);
    }

    protected function createBilledVoucherCharge(
        FeeVoucher $voucher,
        ?StudentEnrollmentRecord $enrollment,
        ?FeeHead $feeHead,
        string $description,
        float $amount,
        float $discount,
        VoucherItemSource $sourceType,
        string $chargeSourceType,
        ?int $sourceId,
        ?int $sourceItemId,
        string $chargeCategory,
        bool $isRecurring,
        array $meta = []
    ): FeeVoucherItem {
        $enrollment ??= $voucher->enrollmentRecord;

        if (! $enrollment) {
            throw new \RuntimeException('Voucher enrollment record is required to create a billed student charge.');
        }

        $netAmount = max(0, $amount - $discount);

        $charge = $this->studentBillingService->createVoucherCharge($voucher, $enrollment, [
            'source_module' => 'fee',
            'source_type' => $chargeSourceType,
            'source_id' => $sourceId,
            'source_item_id' => $sourceItemId,
            'charge_category' => $chargeCategory,
            'title' => $feeHead?->name ?? $description,
            'description' => $description,
            'amount' => $amount,
            'discount_amount' => $discount,
            'net_amount' => $netAmount,
            'is_recurring' => $isRecurring,
            'meta' => $meta,
            'created_by' => auth()->id(),
            'approved_by' => auth()->id(),
        ]);

        $item = $voucher->items()->create([
            'student_account_charge_id' => $charge->id,
            'fee_head_id' => $feeHead?->id,
            'description' => $description,
            'amount' => $amount,
            'discount_amount' => $discount,
            'net_amount' => $netAmount,
            'source_type' => $sourceType,
            'source_module' => 'fee',
            'reference_id' => $sourceId,
            'reference_item_id' => $sourceItemId,
        ]);

        $this->studentBillingService->linkChargeToVoucherItem($charge, $item);

        return $item;
    }

    protected function attachOpenInventoryCharges(FeeVoucher $voucher, StudentEnrollmentRecord $enrollment): void
    {
        $inventoryCharges = $enrollment->student
            ->accountCharges()
            ->where('source_module', 'inventory')
            ->whereIn('status', ['open', 'partial'])
            ->where('billing_status', 'unbilled')
            ->whereNull('voucher_id')
            ->orderBy('charge_date')
            ->get();

        foreach ($inventoryCharges as $charge) {
            $item = $voucher->items()->create([
                'student_account_charge_id' => $charge->id,
                'fee_head_id' => null,
                'description' => $charge->title,
                'amount' => (float) $charge->amount,
                'discount_amount' => (float) $charge->discount_amount,
                'fine_amount' => (float) $charge->fine_amount,
                'net_amount' => (float) $charge->net_amount,
                'source_type' => VoucherItemSource::MANUAL,
                'source_module' => 'inventory',
                'reference_id' => $charge->source_id,
                'reference_item_id' => $charge->source_item_id,
            ]);

            $charge->update([
                'voucher_id' => $voucher->id,
                'voucher_item_id' => $item->id,
                'billing_status' => 'billed',
            ]);
        }
    }

    protected function attachOpenTransportCharges(FeeVoucher $voucher, StudentEnrollmentRecord $enrollment): void
    {
        $transportCharges = $enrollment->student
            ->accountCharges()
            ->where('source_module', 'transport')
            ->whereIn('status', ['open', 'partial'])
            ->where('billing_status', 'unbilled')
            ->whereNull('voucher_id')
            ->orderBy('charge_date')
            ->get();

        foreach ($transportCharges as $charge) {
            $item = $voucher->items()->create([
                'student_account_charge_id' => $charge->id,
                'fee_head_id' => null,
                'description' => $charge->description ?: $charge->title,
                'amount' => (float) $charge->amount,
                'discount_amount' => (float) $charge->discount_amount,
                'fine_amount' => (float) $charge->fine_amount,
                'net_amount' => (float) $charge->net_amount,
                'source_type' => VoucherItemSource::MANUAL,
                'source_module' => 'transport',
                'reference_id' => $charge->source_id,
                'reference_item_id' => $charge->source_item_id,
            ]);

            $charge->update([
                'billing_status' => 'billed',
                'voucher_id' => $voucher->id,
                'voucher_item_id' => $item->id,
            ]);
        }
    }

    /**
     * Check if student has any existing vouchers in this session
     */
    /**
     * Whether the student already had a voucher in this session before this one.
     *
     * The voucher being built is excluded: items are added after it is saved, so
     * counting it made every voucher look like a repeat and one-time charges
     * were never billed at all.
     */
    protected function hasExistingVouchers(StudentEnrollmentRecord $enrollment, ?FeeVoucher $except = null): bool
    {
        return FeeVoucher::where('student_id', $enrollment->student_id)
            ->where('session_id', $enrollment->session_id)
            ->when($except?->exists, fn ($q) => $q->whereKeyNot($except->getKey()))
            ->exists();
    }
}
