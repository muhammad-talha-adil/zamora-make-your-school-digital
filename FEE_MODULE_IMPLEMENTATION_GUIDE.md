# Fee Module Implementation Guide

## Quick Start

### Step 1: Run Migrations

```bash
php artisan migrate
```

This will create all 14 fee-related tables in the correct order.

### Step 2: Seed Master Data

Create a seeder for initial fee heads:

```bash
php artisan make:seeder FeeHeadSeeder
```

Example seeder content:

```php
<?php

namespace Database\Seeders;

use App\Enums\Fee\FeeFrequency;
use App\Enums\Fee\FeeHeadCategory;
use App\Models\Fee\FeeHead;
use Illuminate\Database\Seeder;

class FeeHeadSeeder extends Seeder
{
    public function run(): void
    {
        $feeHeads = [
            [
                'name' => 'Tuition Fee',
                'code' => 'TUITION',
                'category' => FeeHeadCategory::MONTHLY,
                'is_recurring' => true,
                'default_frequency' => FeeFrequency::MONTHLY,
                'is_optional' => false,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Admission Fee',
                'code' => 'ADMISSION',
                'category' => FeeHeadCategory::ONE_TIME,
                'is_recurring' => false,
                'default_frequency' => FeeFrequency::ONCE,
                'is_optional' => false,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Annual Charges',
                'code' => 'ANNUAL',
                'category' => FeeHeadCategory::ANNUAL,
                'is_recurring' => true,
                'default_frequency' => FeeFrequency::YEARLY,
                'is_optional' => false,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Transport Fee',
                'code' => 'TRANSPORT',
                'category' => FeeHeadCategory::TRANSPORT,
                'is_recurring' => true,
                'default_frequency' => FeeFrequency::MONTHLY,
                'is_optional' => true,
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Computer Lab Fee',
                'code' => 'COMPUTER',
                'category' => FeeHeadCategory::MONTHLY,
                'is_recurring' => true,
                'default_frequency' => FeeFrequency::MONTHLY,
                'is_optional' => true,
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Exam Fee',
                'code' => 'EXAM',
                'category' => FeeHeadCategory::MISC,
                'is_recurring' => false,
                'default_frequency' => FeeFrequency::ONCE,
                'is_optional' => false,
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'Late Payment Fine',
                'code' => 'FINE',
                'category' => FeeHeadCategory::FINE,
                'is_recurring' => false,
                'default_frequency' => FeeFrequency::ONCE,
                'is_optional' => false,
                'sort_order' => 7,
                'is_active' => true,
            ],
        ];

        foreach ($feeHeads as $feeHead) {
            FeeHead::create($feeHead);
        }
    }
}
```

Run the seeder:

```bash
php artisan db:seed --class=FeeHeadSeeder
```

### Step 3: Create Discount Types

```bash
php artisan make:seeder DiscountTypeSeeder
```

Example:

```php
<?php

namespace Database\Seeders;

use App\Enums\Fee\ValueType;
use App\Models\Fee\DiscountType;
use Illuminate\Database\Seeder;

class DiscountTypeSeeder extends Seeder
{
    public function run(): void
    {
        $discountTypes = [
            [
                'name' => 'Sibling Discount',
                'code' => 'SIBLING',
                'value_type' => ValueType::PERCENT,
                'default_value' => 10,
                'description' => '10% discount for second child, 20% for third',
                'is_active' => true,
            ],
            [
                'name' => 'Merit Scholarship',
                'code' => 'MERIT',
                'value_type' => ValueType::PERCENT,
                'default_value' => 50,
                'description' => 'Merit-based scholarship for top performers',
                'is_active' => true,
            ],
            [
                'name' => 'Staff Child Discount',
                'code' => 'STAFF',
                'value_type' => ValueType::PERCENT,
                'default_value' => 100,
                'description' => 'Full fee waiver for staff children',
                'is_active' => true,
            ],
            [
                'name' => 'Financial Aid',
                'code' => 'FINANCIAL_AID',
                'value_type' => ValueType::FIXED,
                'default_value' => null,
                'description' => 'Need-based financial assistance',
                'is_active' => true,
            ],
        ];

        foreach ($discountTypes as $discountType) {
            DiscountType::create($discountType);
        }
    }
}
```

## Typical Workflow

### 1. Setup Fee Structure for a Class

```php
use App\Models\Fee\FeeStructure;
use App\Models\Fee\FeeStructureItem;
use App\Enums\Fee\FeeStructureStatus;
use App\Enums\Fee\FeeFrequency;

// Create structure
$structure = FeeStructure::create([
    'title' => 'Grade 10 Fee Plan 2024-2025',
    'session_id' => 1,
    'campus_id' => 1,
    'class_id' => 10,
    'section_id' => null, // Applies to all sections
    'is_default' => true,
    'effective_from' => '2024-08-01',
    'effective_to' => '2025-06-30',
    'status' => FeeStructureStatus::ACTIVE,
    'created_by' => auth()->id(),
]);

// Add monthly tuition
$structure->items()->create([
    'fee_head_id' => 1, // Tuition Fee
    'amount' => 5000,
    'frequency' => FeeFrequency::MONTHLY,
    'starts_from_month_id' => 8, // August (month ID)
    'ends_at_month_id' => 6, // June (month ID)
    'is_optional' => false,
]);

// Add annual charges (August only)
$structure->items()->create([
    'fee_head_id' => 3, // Annual Charges
    'amount' => 3000,
    'frequency' => FeeFrequency::YEARLY,
    'billing_month_id' => 8, // August (month ID)
    'is_optional' => false,
]);

// Add optional transport
$structure->items()->create([
    'fee_head_id' => 4, // Transport Fee
    'amount' => 1500,
    'frequency' => FeeFrequency::MONTHLY,
    'starts_from_month_id' => 8,
    'ends_at_month_id' => 6,
    'is_optional' => true,
    'is_transport_related' => true,
]);
```

### 2. Apply Student Discount

```php
use App\Models\Fee\StudentDiscount;
use App\Enums\Fee\ValueType;
use App\Enums\Fee\ApprovalStatus;

$discount = StudentDiscount::create([
    'student_id' => $student->id,
    'student_enrollment_record_id' => $enrollment->id,
    'discount_type_id' => 1, // Sibling Discount
    'fee_head_id' => null, // Applies to all fees
    'value_type' => ValueType::PERCENT,
    'value' => 10,
    'effective_from' => now(),
    'effective_to' => null,
    'approval_status' => ApprovalStatus::APPROVED,
    'approved_by' => auth()->id(),
    'reason' => 'Second child in school',
]);
```

### 3. Generate Monthly Vouchers

```php
use App\Models\Fee\FeeVoucher;
use App\Models\Fee\FeeVoucherItem;
use App\Enums\Fee\VoucherStatus;
use App\Enums\Fee\VoucherItemSource;

// For each student in class
foreach ($students as $student) {
    $enrollment = $student->currentEnrollment;
    
    // Get applicable fee structure
    $structure = FeeStructure::active()
        ->forSession($enrollment->session_id)
        ->forCampus($enrollment->campus_id)
        ->forClass($enrollment->class_id)
        ->effectiveOn(now())
        ->first();
    
    // Generate voucher
    $voucher = FeeVoucher::create([
        'voucher_no' => 'FV-' . now()->format('Y') . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT),
        'student_id' => $student->id,
        'student_enrollment_record_id' => $enrollment->id,
        'session_id' => $enrollment->session_id,
        'campus_id' => $enrollment->campus_id,
        'class_id' => $enrollment->class_id,
        'section_id' => $enrollment->section_id,
        'voucher_month' => 9, // September
        'voucher_year' => 2024,
        'issue_date' => now(),
        'due_date' => now()->addDays(15),
        'status' => VoucherStatus::UNPAID,
        'generated_by' => auth()->id(),
    ]);
    
    $grossAmount = 0;
    $discountAmount = 0;
    
    // Add items from structure
    foreach ($structure->items as $item) {
        if ($item->isApplicableForMonth(9)) {
            $amount = $item->amount;
            $discount = 0;
            
            // Apply student discounts
            $studentDiscount = $student->discounts()
                ->approved()
                ->effectiveOn(now())
                ->where(function($q) use ($item) {
                    $q->whereNull('fee_head_id')
                      ->orWhere('fee_head_id', $item->fee_head_id);
                })
                ->first();
            
            if ($studentDiscount) {
                if ($studentDiscount->value_type === ValueType::PERCENT) {
                    $discount = ($amount * $studentDiscount->value) / 100;
                } else {
                    $discount = $studentDiscount->value;
                }
            }
            
            $voucher->items()->create([
                'fee_head_id' => $item->fee_head_id,
                'description' => $item->feeHead->name . ' - September 2024',
                'amount' => $amount,
                'discount_amount' => $discount,
                'net_amount' => $amount - $discount,
                'source_type' => VoucherItemSource::STRUCTURE,
                'reference_id' => $item->id,
            ]);
            
            $grossAmount += $amount;
            $discountAmount += $discount;
        }
    }
    
    // Update voucher totals
    $voucher->update([
        'gross_amount' => $grossAmount,
        'discount_amount' => $discountAmount,
        'net_amount' => $grossAmount - $discountAmount,
        'balance_amount' => $grossAmount - $discountAmount,
    ]);
}
```

### 4. Record Payment

```php
use App\Models\Fee\FeePayment;
use App\Models\Fee\FeePaymentAllocation;
use App\Enums\Fee\PaymentMethod;
use App\Enums\Fee\PaymentStatus;

// Create payment
$payment = FeePayment::create([
    'receipt_no' => 'RCP-' . now()->format('Y') . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT),
    'student_id' => $student->id,
    'student_enrollment_record_id' => $enrollment->id,
    'payment_date' => now(),
    'payment_method' => PaymentMethod::CASH,
    'received_amount' => 6500,
    'status' => PaymentStatus::POSTED,
    'received_by' => auth()->id(),
]);

// Allocate to voucher(s)
$remainingAmount = $payment->received_amount;

foreach ($unpaidVouchers as $voucher) {
    if ($remainingAmount <= 0) break;
    
    $allocationAmount = min($remainingAmount, $voucher->balance_amount);
    
    // Create allocation
    FeePaymentAllocation::create([
        'fee_payment_id' => $payment->id,
        'fee_voucher_id' => $voucher->id,
        'allocated_amount' => $allocationAmount,
        'allocation_date' => now(),
    ]);
    
    // Update voucher
    $voucher->paid_amount += $allocationAmount;
    $voucher->balance_amount -= $allocationAmount;
    
    if ($voucher->balance_amount <= 0) {
        $voucher->status = VoucherStatus::PAID;
    } else {
        $voucher->status = VoucherStatus::PARTIAL;
    }
    
    $voucher->save();
    
    $remainingAmount -= $allocationAmount;
}

// Update payment
$payment->update([
    'allocated_amount' => $payment->received_amount - $remainingAmount,
    'remaining_unallocated_amount' => $remainingAmount,
    'excess_amount' => $remainingAmount > 0 ? $remainingAmount : 0,
]);

// If excess, add to wallet
if ($remainingAmount > 0) {
    StudentFeeWalletTransaction::create([
        'student_id' => $student->id,
        'transaction_date' => now(),
        'transaction_type' => WalletTransactionType::ADVANCE_DEPOSIT,
        'direction' => WalletDirection::CREDIT,
        'amount' => $remainingAmount,
        'reference_type' => 'fee_payment',
        'reference_id' => $payment->id,
        'description' => 'Excess payment added to wallet',
        'created_by' => auth()->id(),
    ]);
}
```

### 5. Apply Late Fine

```php
use App\Models\Fee\FeeFineRule;
use App\Models\Fee\FeeVoucherItem;
use App\Enums\Fee\VoucherItemSource;

// Find applicable fine rule
$fineRule = FeeFineRule::active()
    ->where('campus_id', $voucher->campus_id)
    ->where('session_id', $voucher->session_id)
    ->effectiveOn(now())
    ->first();

if ($fineRule && $voucher->due_date < now()) {
    $daysLate = now()->diffInDays($voucher->due_date);
    $fineAmount = $fineRule->calculateFine($voucher->net_amount, $daysLate);
    
    if ($fineAmount > 0) {
        // Add fine item
        $voucher->items()->create([
            'fee_head_id' => 7, // Late Payment Fine
            'description' => 'Late payment fine (' . $daysLate . ' days)',
            'amount' => $fineAmount,
            'net_amount' => $fineAmount,
            'source_type' => VoucherItemSource::MANUAL,
        ]);
        
        // Update voucher
        $voucher->fine_amount += $fineAmount;
        $voucher->net_amount += $fineAmount;
        $voucher->balance_amount += $fineAmount;
        $voucher->status = VoucherStatus::OVERDUE;
        $voucher->save();
    }
}
```

## Service Layer Example

Create a service for voucher generation:

```php
<?php

namespace App\Services\Fee;

use App\Models\Fee\FeeVoucher;
use App\Models\Fee\FeeStructure;
use App\Models\StudentEnrollmentRecord;
use App\Enums\Fee\VoucherStatus;
use App\Enums\Fee\VoucherItemSource;
use App\Enums\Fee\ValueType;
use Illuminate\Support\Facades\DB;

class VoucherGenerationService
{
    public function generateMonthlyVouchers(int $month, int $year, array $filters = [])
    {
        return DB::transaction(function () use ($month, $year, $filters) {
            $query = StudentEnrollmentRecord::active()
                ->with(['student', 'session', 'campus', 'class', 'section']);
            
            // Apply filters
            if (isset($filters['campus_id'])) {
                $query->where('campus_id', $filters['campus_id']);
            }
            if (isset($filters['class_id'])) {
                $query->where('class_id', $filters['class_id']);
            }
            if (isset($filters['section_id'])) {
                $query->where('section_id', $filters['section_id']);
            }
            
            $enrollments = $query->get();
            $generated = 0;
            
            foreach ($enrollments as $enrollment) {
                // Check if voucher already exists
                $exists = FeeVoucher::where('student_id', $enrollment->student_id)
                    ->where('voucher_month', $month)
                    ->where('voucher_year', $year)
                    ->exists();
                
                if ($exists) {
                    continue;
                }
                
                $this->generateVoucherForStudent($enrollment, $month, $year);
                $generated++;
            }
            
            return $generated;
        });
    }
    
    protected function generateVoucherForStudent(
        StudentEnrollmentRecord $enrollment,
        int $month,
        int $year
    ): FeeVoucher {
        // Get applicable fee structure
        $structure = $this->getApplicableFeeStructure($enrollment);
        
        if (!$structure) {
            throw new \Exception('No fee structure found for student');
        }
        
        // Generate voucher number
        $voucherNo = $this->generateVoucherNumber($year);
        
        // Create voucher
        $voucher = FeeVoucher::create([
            'voucher_no' => $voucherNo,
            'student_id' => $enrollment->student_id,
            'student_enrollment_record_id' => $enrollment->id,
            'session_id' => $enrollment->session_id,
            'campus_id' => $enrollment->campus_id,
            'class_id' => $enrollment->class_id,
            'section_id' => $enrollment->section_id,
            'voucher_month' => $month,
            'voucher_year' => $year,
            'issue_date' => now(),
            'due_date' => now()->addDays(15),
            'status' => VoucherStatus::UNPAID,
            'generated_by' => auth()->id(),
        ]);
        
        // Add items
        $this->addVoucherItems($voucher, $structure, $enrollment, $month);
        
        // Calculate totals
        $this->calculateVoucherTotals($voucher);
        
        return $voucher;
    }
    
    protected function getApplicableFeeStructure(StudentEnrollmentRecord $enrollment): ?FeeStructure
    {
        // Try section-specific first
        $structure = FeeStructure::active()
            ->forSession($enrollment->session_id)
            ->forCampus($enrollment->campus_id)
            ->forClass($enrollment->class_id)
            ->forSection($enrollment->section_id)
            ->effectiveOn(now())
            ->first();
        
        if ($structure) return $structure;
        
        // Try class-specific
        $structure = FeeStructure::active()
            ->forSession($enrollment->session_id)
            ->forCampus($enrollment->campus_id)
            ->forClass($enrollment->class_id)
            ->whereNull('section_id')
            ->effectiveOn(now())
            ->first();
        
        if ($structure) return $structure;
        
        // Try campus-wide
        return FeeStructure::active()
            ->forSession($enrollment->session_id)
            ->forCampus($enrollment->campus_id)
            ->whereNull('class_id')
            ->whereNull('section_id')
            ->effectiveOn(now())
            ->first();
    }
    
    protected function generateVoucherNumber(int $year): string
    {
        $lastVoucher = FeeVoucher::where('voucher_year', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        $nextNumber = $lastVoucher ? ((int) substr($lastVoucher->voucher_no, -6)) + 1 : 1;
        
        return 'FV-' . $year . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
    
    protected function addVoucherItems(
        FeeVoucher $voucher,
        FeeStructure $structure,
        StudentEnrollmentRecord $enrollment,
        int $month
    ): void {
        foreach ($structure->items as $item) {
            if (!$item->isApplicableForMonth($month)) {
                continue;
            }
            
            $amount = $item->amount;
            $discount = $this->calculateDiscount($enrollment->student, $item, $amount);
            
            $voucher->items()->create([
                'fee_head_id' => $item->fee_head_id,
                'description' => $item->feeHead->name . ' - ' . date('F Y', mktime(0, 0, 0, $month, 1, $voucher->voucher_year)),
                'amount' => $amount,
                'discount_amount' => $discount,
                'net_amount' => $amount - $discount,
                'source_type' => VoucherItemSource::STRUCTURE,
                'reference_id' => $item->id,
            ]);
        }
    }
    
    protected function calculateDiscount($student, $structureItem, float $amount): float
    {
        $discount = $student->discounts()
            ->approved()
            ->effectiveOn(now())
            ->where(function($q) use ($structureItem) {
                $q->whereNull('fee_head_id')
                  ->orWhere('fee_head_id', $structureItem->fee_head_id);
            })
            ->first();
        
        if (!$discount) {
            return 0;
        }
        
        if ($discount->value_type === ValueType::PERCENT) {
            return ($amount * $discount->value) / 100;
        }
        
        return $discount->value;
    }
    
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
}
```

## Controller Example

```php
<?php

namespace App\Http\Controllers\Fee;

use App\Http\Controllers\Controller;
use App\Services\Fee\VoucherGenerationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VoucherController extends Controller
{
    protected $voucherService;
    
    public function __construct(VoucherGenerationService $voucherService)
    {
        $this->voucherService = $voucherService;
    }
    
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020',
            'campus_id' => 'nullable|exists:campuses,id',
            'class_id' => 'nullable|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
        ]);
        
        $generated = $this->voucherService->generateMonthlyVouchers(
            $validated['month'],
            $validated['year'],
            $validated
        );
        
        return back()->with('success', "Generated {$generated} vouchers successfully.");
    }
}
```

## Testing

Create tests for critical functionality:

```bash
php artisan make:test Fee/VoucherGenerationTest
```

Example test:

```php
<?php

namespace Tests\Feature\Fee;

use Tests\TestCase;
use App\Models\Student;
use App\Models\Fee\FeeStructure;
use App\Models\Fee\FeeVoucher;
use App\Services\Fee\VoucherGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VoucherGenerationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_voucher_generation_creates_voucher_for_student()
    {
        // Arrange
        $student = Student::factory()->create();
        $enrollment = $student->enrollmentRecords()->create([/* ... */]);
        $structure = FeeStructure::factory()->create([/* ... */]);
        
        $service = new VoucherGenerationService();
        
        // Act
        $generated = $service->generateMonthlyVouchers(9, 2024);
        
        // Assert
        $this->assertEquals(1, $generated);
        $this->assertDatabaseHas('fee_vouchers', [
            'student_id' => $student->id,
            'voucher_month' => 9,
            'voucher_year' => 2024,
        ]);
    }
}
```

## Next Steps

1. **Build Controllers**: Create controllers for all CRUD operations
2. **Create Form Requests**: Validation classes for all forms
3. **Build Vue Components**: UI for fee management
4. **Add Permissions**: Define and implement role-based permissions
5. **Create Reports**: Fee collection, outstanding, defaulters reports
6. **Add Notifications**: Email/SMS for vouchers and payments
7. **Build API**: RESTful API for mobile apps
8. **Write Documentation**: User manual and API documentation
9. **Performance Testing**: Load testing with 10,000+ students
10. **Deploy**: Production deployment checklist

## Conclusion

This implementation guide provides a complete roadmap for implementing the fee module. Follow the steps sequentially, test thoroughly, and customize as needed for your specific requirements.
