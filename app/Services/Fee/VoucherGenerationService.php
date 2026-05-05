<?php

namespace App\Services\Fee;

use App\Enums\Fee\ValueType;
use App\Enums\Fee\VoucherItemSource;
use App\Enums\Fee\VoucherStatus;
use App\Models\Fee\FeeHead;
use App\Models\Fee\FeeStructure;
use App\Models\Fee\FeeVoucher;
use App\Models\Fee\StudentPaidOneTimeFee;
use App\Models\Month;
use App\Models\StudentEnrollmentRecord;
use App\Traits\Fee\HasMonthHelpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VoucherGenerationService
{
    use HasMonthHelpers;

    /**
     * Generate monthly vouchers for students
     */
    public function generateMonthlyVouchers(int $monthNumber, int $year, array $filters = []): array
    {
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
                        $includePreviousUnpaid
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
        bool $includePreviousUnpaid = false
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
            // Create a previous balance item
            $previousBalanceHead = FeeHead::where('code', 'PREVIOUS_BALANCE')->first();

            if (! $previousBalanceHead) {
                // Create the previous balance fee head if it doesn't exist
                $previousBalanceHead = FeeHead::create([
                    'name' => 'Previous Balance',
                    'code' => 'PREVIOUS_BALANCE',
                    'category' => 'other',
                    'is_recurring' => false,
                    'default_frequency' => 'once',
                    'is_optional' => false,
                    'is_active' => true,
                ]);
            }

            $voucher->items()->create([
                'fee_head_id' => $previousBalanceHead->id,
                'description' => 'Previous Outstanding Balance (Arrears)',
                'quantity' => 1,
                'unit_price' => $totalPreviousBalance,
                'amount' => $totalPreviousBalance,
                'discount_amount' => 0,
                'net_amount' => $totalPreviousBalance,
                'source_type' => VoucherItemSource::ARREARS,
                'reference_id' => null,
            ]);
        }

        return $previousVoucherIds;
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
        // 1. First check if there's a direct fee structure assigned to the enrollment
        // Use the preloaded relationship if available
        if ($enrollment->fee_structure_id && $enrollment->relationLoaded('feeStructure')) {
            $structure = $enrollment->feeStructure;
            if ($structure && $structure->isEffectiveOn(now())) {
                return $structure;
            }
        }

        // 2. Try section-specific
        $structure = FeeStructure::active()
            ->with('items')
            ->where('session_id', $enrollment->session_id)
            ->where('campus_id', $enrollment->campus_id)
            ->where('class_id', $enrollment->class_id)
            ->where('section_id', $enrollment->section_id)
            ->effectiveOn(now())
            ->first();

        if ($structure) {
            return $structure;
        }

        // 3. Try class-specific
        $structure = FeeStructure::active()
            ->with('items')
            ->where('session_id', $enrollment->session_id)
            ->where('campus_id', $enrollment->campus_id)
            ->where('class_id', $enrollment->class_id)
            ->whereNull('section_id')
            ->effectiveOn(now())
            ->first();

        if ($structure) {
            return $structure;
        }

        // 4. Try campus-wide
        return FeeStructure::active()
            ->with('items')
            ->where('session_id', $enrollment->session_id)
            ->where('campus_id', $enrollment->campus_id)
            ->whereNull('class_id')
            ->whereNull('section_id')
            ->effectiveOn(now())
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
        // Get student-specific custom fee assignments (disabled per user request)
        $customAssignments = [];

        // Get already paid fee heads for this student in this session
        $paidFeeHeadIds = $this->getPaidFeeHeadIds($enrollment);

        // Check if this is the first voucher of the session for one-time fees
        $firstVoucherOfSession = ! $this->hasExistingVouchers($enrollment);

        // If we have a fee structure, use it
        if ($structure && $structure->items->isNotEmpty()) {
            // Check if this is the first month of session for annual fees
            $sessionStartMonth = $enrollment->session->start_date ?? now();
            $firstMonthOfSession = (int) date('n', strtotime($sessionStartMonth));
            $isFirstMonth = $firstMonthOfSession == $monthNumber;

            foreach ($structure->items as $item) {
                // Check frequency - skip if yearly and not first month
                if ($item->frequency === 'yearly' && ! $isFirstMonth) {
                    continue;
                }

                // NEW: Handle "once" frequency - only add on first voucher of session
                if ($item->frequency === 'once') {
                    // Only add if this is the first voucher of the session
                    if (! $firstVoucherOfSession) {
                        continue;
                    }
                    // Also check if already paid (for readmitted students)
                    if (StudentPaidOneTimeFee::hasPaid($enrollment->student_id, $item->fee_head_id)) {
                        continue;
                    }
                }

                // Check if item is applicable for this month based on month range
                if ($item->frequency !== 'once' && ! $item->isApplicableForMonth($monthNumber)) {
                    continue;
                }

                // Skip if this fee head was already paid in a previous voucher
                if (in_array($item->fee_head_id, $paidFeeHeadIds)) {
                    Log::info('Skipping fee head - already paid', [
                        'student_id' => $enrollment->student_id,
                        'fee_head_id' => $item->fee_head_id,
                        'fee_head' => $item->feeHead->name,
                        'voucher_month' => $monthNumber,
                    ]);

                    continue;
                }

                $amount = $item->amount;
                // Check if student has custom assignment for this fee head
                $customAmount = $this->getCustomAmountForFeeHead($customAssignments, $item->fee_head_id);
                if ($customAmount !== null) {
                    $amount = $customAmount;
                }

                $discount = $this->calculateDiscount($enrollment->student, $item, $amount, $enrollment->id);

                $voucher->items()->create([
                    'fee_head_id' => $item->fee_head_id,
                    'description' => $item->feeHead->name.($item->frequency === 'once' ? ' (One Time)' : ' - '.self::getMonthNameByNumber($monthNumber).' '.$voucher->voucher_year),
                    'amount' => $amount,
                    'discount_amount' => $discount,
                    'net_amount' => $amount - $discount,
                    'source_type' => VoucherItemSource::STRUCTURE,
                    'reference_id' => $item->id,
                ]);

                // Record one-time fee payment if applicable
                if ($item->frequency === 'once' && ($amount - $discount) > 0) {
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
            $this->addVoucherItemsFromEnrollment($voucher, $enrollment, $monthNumber, $customAssignments, $paidFeeHeadIds);
        }

        // Add custom fee heads that were provided during generation (skip if already paid)
        $this->addCustomFeeHeads($voucher, $customFeeHeads, $monthNumber, $paidFeeHeadIds);
    }

    /**
     * Get list of fee head IDs that have already been paid for this student in this session
     * Also checks the StudentPaidOneTimeFee table for one-time fees
     */
    protected function getPaidFeeHeadIds(StudentEnrollmentRecord $enrollment): array
    {
        $paidFeeHeadIds = [];

        // Get all paid vouchers for this student in this session
        $paidVouchers = FeeVoucher::where('student_id', $enrollment->student_id)
            ->where('session_id', $enrollment->session_id)
            ->whereIn('status', ['paid', 'partial'])
            ->with(['items'])
            ->get();

        foreach ($paidVouchers as $voucher) {
            foreach ($voucher->items as $item) {
                // Track which fee heads have been paid
                if (! in_array($item->fee_head_id, $paidFeeHeadIds)) {
                    $paidFeeHeadIds[] = $item->fee_head_id;
                }
            }
        }

        // Also check the StudentPaidOneTimeFee table for one-time fees
        $oneTimeFees = StudentPaidOneTimeFee::where('student_id', $enrollment->student_id)->get();
        foreach ($oneTimeFees as $otf) {
            if (! in_array($otf->fee_head_id, $paidFeeHeadIds)) {
                $paidFeeHeadIds[] = $otf->fee_head_id;
            }
        }

        return $paidFeeHeadIds;
    }

    /**
     * Add custom fee heads that were specified during voucher generation
     * Skips fee heads that have already been paid
     */
    protected function addCustomFeeHeads(FeeVoucher $voucher, array $customFeeHeads, int $monthNumber, array $paidFeeHeadIds = []): void
    {
        if (empty($customFeeHeads)) {
            return;
        }

        $month = Month::find($voucher->voucher_month_id);
        $monthName = $month ? $month->name : self::getMonthNameByNumber($monthNumber);

        foreach ($customFeeHeads as $customFee) {
            // Skip if this fee head was already paid
            if (in_array($customFee['fee_head_id'], $paidFeeHeadIds)) {
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

            $voucher->items()->create([
                'fee_head_id' => $customFee['fee_head_id'],
                'description' => $feeHead->name.' - '.$monthName.' '.$voucher->voucher_year,
                'quantity' => 1,
                'unit_price' => $customFee['amount'],
                'amount' => $customFee['amount'],
                'discount_amount' => 0,
                'net_amount' => $customFee['amount'],
                'source_type' => VoucherItemSource::MANUAL,
                'reference_id' => null,
            ]);
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
        array $paidFeeHeadIds = []
    ): void {
        // Get or create Monthly and Annual fee heads
        $monthlyFeeHead = FeeHead::where('code', 'MONTHLY_TUITION')->first();
        $annualFeeHead = FeeHead::where('code', 'ANNUAL')->first();

        // Check if this is the first month of session for annual fee
        $sessionStartMonth = $enrollment->session->start_date ?? now();
        $isFirstMonth = date('n', strtotime($sessionStartMonth)) == $monthNumber;

        // Add monthly tuition fee (skip if already paid)
        if ($monthlyFeeHead && ! in_array($monthlyFeeHead->id, $paidFeeHeadIds)) {
            // Check for custom amount first, then fall back to enrollment
            $customMonthlyAmount = $this->getCustomAmountForFeeHead($customAssignments, $monthlyFeeHead->id);
            $monthlyAmount = $customMonthlyAmount ?? $enrollment->monthly_fee ?? 0;

            if ($monthlyAmount > 0) {
                $discount = $this->calculateDiscountForFeeHead($enrollment->student, $monthlyFeeHead->id, $monthlyAmount, $enrollment->id);

                $voucher->items()->create([
                    'fee_head_id' => $monthlyFeeHead->id,
                    'description' => $monthlyFeeHead->name.' - '.self::getMonthNameByNumber($monthNumber).' '.$voucher->voucher_year,
                    'amount' => $monthlyAmount,
                    'discount_amount' => $discount,
                    'net_amount' => $monthlyAmount - $discount,
                    'source_type' => VoucherItemSource::STRUCTURE,
                    'reference_id' => null,
                ]);
            }
        }

        // Add annual fee (only in first month of session and if not already paid)
        if ($annualFeeHead && $isFirstMonth && ! in_array($annualFeeHead->id, $paidFeeHeadIds)) {
            $customAnnualAmount = $this->getCustomAmountForFeeHead($customAssignments, $annualFeeHead->id);
            $annualAmount = $customAnnualAmount ?? $enrollment->annual_fee ?? 0;

            if ($annualAmount > 0) {
                $discount = $this->calculateDiscountForFeeHead($enrollment->student, $annualFeeHead->id, $annualAmount, $enrollment->id);

                $voucher->items()->create([
                    'fee_head_id' => $annualFeeHead->id,
                    'description' => $annualFeeHead->name.' - '.$voucher->voucher_year,
                    'amount' => $annualAmount,
                    'discount_amount' => $discount,
                    'net_amount' => $annualAmount - $discount,
                    'source_type' => VoucherItemSource::STRUCTURE,
                    'reference_id' => null,
                ]);
            }
        }

        Log::info('Voucher items added from enrollment (no fee structure found)', [
            'voucher_id' => $voucher->id,
            'student_id' => $enrollment->student_id,
            'monthly_fee' => $enrollment->monthly_fee,
            'annual_fee' => $enrollment->annual_fee,
            'custom_assignments' => count($customAssignments),
            'paid_fee_head_ids' => $paidFeeHeadIds,
        ]);
    }

    /**
     * Get student-specific fee assignments
     */
    protected function getStudentFeeAssignments(StudentEnrollmentRecord $enrollment): array
    {
        $assignments = $enrollment->student
            ->feeAssignments()
            ->where('is_active', true)
            ->where('assignment_type', 'custom')
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
     * Calculate discount for a specific fee head
     */
    protected function calculateDiscountForFeeHead($student, int $feeHeadId, float $amount, ?int $enrollmentId = null): float
    {
        $query = $student->discounts()
            ->approved()
            ->effectiveOn(now())
            ->where(function ($q) use ($feeHeadId) {
                $q->whereNull('fee_head_id')
                    ->orWhere('fee_head_id', $feeHeadId);
            });

        // Filter by enrollment if provided
        if ($enrollmentId) {
            $query->where('student_enrollment_record_id', $enrollmentId);
        }

        $discount = $query->first();

        if (! $discount) {
            return 0;
        }

        if ($discount->value_type === ValueType::PERCENT) {
            return ($amount * $discount->value) / 100;
        }

        return (float) $discount->value;
    }

    /**
     * Calculate discount for student
     */
    protected function calculateDiscount($student, $structureItem, float $amount, ?int $enrollmentId = null): float
    {
        $query = $student->discounts()
            ->approved()
            ->effectiveOn(now())
            ->where(function ($q) use ($structureItem) {
                $q->whereNull('fee_head_id')
                    ->orWhere('fee_head_id', $structureItem->fee_head_id);
            });

        // Filter by enrollment if provided
        if ($enrollmentId) {
            $query->where('student_enrollment_record_id', $enrollmentId);
        }

        $discount = $query->first();

        if (! $discount) {
            return 0;
        }

        if ($discount->value_type === ValueType::PERCENT) {
            return ($amount * $discount->value) / 100;
        }

        return $discount->value;
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

    /**
     * Check if student has any existing vouchers in this session
     */
    protected function hasExistingVouchers(StudentEnrollmentRecord $enrollment): bool
    {
        return FeeVoucher::where('student_id', $enrollment->student_id)
            ->where('session_id', $enrollment->session_id)
            ->exists();
    }
}
