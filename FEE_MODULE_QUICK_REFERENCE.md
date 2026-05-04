# Fee Module - Quick Reference Card

## Table Structure at a Glance

```
┌─────────────────────────────────────────────────────────────────┐
│                        MASTER DATA                               │
├─────────────────────────────────────────────────────────────────┤
│ fee_heads              - Fee types (Tuition, Transport, etc.)   │
│ discount_types         - Discount programs (Sibling, Merit)     │
│ fee_fine_rules         - Late payment fine rules                │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                      FEE STRUCTURES                              │
├─────────────────────────────────────────────────────────────────┤
│ fee_structures     - Fee plan headers                       │
│ fee_structure_items    - Fee plan details (amounts, rules)      │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                   STUDENT CUSTOMIZATION                          │
├─────────────────────────────────────────────────────────────────┤
│ student_fee_assignments - Ad-hoc overrides, extra charges       │
│ student_discounts       - Formal discounts with approval        │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                    VOUCHERS (CHALLANS)                           │
├─────────────────────────────────────────────────────────────────┤
│ fee_vouchers        - Voucher headers                       │
│ fee_voucher_items       - Line items breakdown                  │
│ fee_voucher_adjustments - Arrears, advance, waivers             │
│ fee_voucher_print_logs  - Print audit trail                     │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                        PAYMENTS                                  │
├─────────────────────────────────────────────────────────────────┤
│ new_fee_payments        - Payment receipts                      │
│ fee_payment_allocations - Payment to voucher mapping            │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                    ADVANCE WALLET                                │
├─────────────────────────────────────────────────────────────────┤
│ student_fee_wallet_transactions - Advance payment ledger        │
└─────────────────────────────────────────────────────────────────┘
```

## Common Queries

### Get Student's Unpaid Vouchers
```php
$unpaidVouchers = FeeVoucher::where('student_id', $studentId)
    ->unpaid()
    ->orderBy('due_date')
    ->get();
```

### Get Student's Wallet Balance
```php
$balance = $student->walletBalance; // Uses accessor
// OR
$balance = StudentFeeWalletTransaction::where('student_id', $studentId)
    ->selectRaw('
        SUM(CASE WHEN direction = "credit" THEN amount ELSE 0 END) -
        SUM(CASE WHEN direction = "debit" THEN amount ELSE 0 END) as balance
    ')
    ->value('balance');
```

### Get Active Fee Structure for Student
```php
$structure = FeeStructure::active()
    ->forSession($enrollment->session_id)
    ->forCampus($enrollment->campus_id)
    ->forClass($enrollment->class_id)
    ->forSection($enrollment->section_id)
    ->effectiveOn(now())
    ->first();
```

### Get Student's Active Discounts
```php
$discounts = StudentDiscount::where('student_id', $studentId)
    ->approved()
    ->effectiveOn(now())
    ->with('discountType', 'feeHead')
    ->get();
```

### Get Overdue Vouchers
```php
$overdueVouchers = FeeVoucher::where('due_date', '<', now())
    ->whereIn('status', [VoucherStatus::UNPAID, VoucherStatus::PARTIAL])
    ->with('student', 'items')
    ->get();
```

### Get Payment History for Student
```php
$payments = FeePayment::where('student_id', $studentId)
    ->with('allocations.voucher')
    ->orderBy('payment_date', 'desc')
    ->get();
```

## Enum Quick Reference

### VoucherStatus
- `UNPAID` - Not paid yet
- `PARTIAL` - Partially paid
- `PAID` - Fully paid
- `OVERDUE` - Past due date
- `CANCELLED` - Cancelled
- `ADJUSTED` - Adjusted (advance/waiver)

### PaymentMethod
- `CASH` - Cash payment
- `BANK` - Bank transfer
- `ONLINE` - Online payment
- `JAZZCASH` - JazzCash mobile wallet
- `EASYPAISA` - EasyPaisa mobile wallet
- `CHEQUE` - Cheque payment

### FeeHeadCategory
- `MONTHLY` - Monthly recurring fee
- `ANNUAL` - Annual recurring fee
- `ONE_TIME` - One-time charge
- `TRANSPORT` - Transport fee
- `FINE` - Fine/penalty
- `DISCOUNT` - Discount/concession
- `MISC` - Miscellaneous

### AssignmentType
- `OVERRIDE` - Override standard fee
- `DISCOUNT` - Apply discount
- `EXTRA_CHARGE` - Add extra charge
- `WAIVER` - Complete waiver

### ValueType
- `FIXED` - Fixed amount
- `PERCENT` - Percentage

## Model Relationships

### Student
```php
$student->feeVouchers
$student->feePayments
$student->feeAssignments
$student->discounts
$student->walletTransactions
$student->walletBalance // Accessor
```

### StudentEnrollmentRecord
```php
$enrollment->feeVouchers
$enrollment->feePayments
$enrollment->feeAssignments
$enrollment->discounts
```

### FeeVoucher
```php
$voucher->student
$voucher->enrollmentRecord
$voucher->items
$voucher->adjustments
$voucher->paymentAllocations
$voucher->printLogs
$voucher->isPaid() // Method
$voucher->isOverdue() // Method
```

### FeePayment
```php
$payment->student
$payment->enrollmentRecord
$payment->allocations
$payment->isFullyAllocated() // Method
```

### FeeStructure
```php
$structure->session
$structure->campus
$structure->schoolClass
$structure->section
$structure->items
```

## Scopes Quick Reference

### FeeVoucher Scopes
```php
->byStatus(VoucherStatus $status)
->unpaid()
->forPeriod(int $month, int $year)
->published()
```

### FeePayment Scopes
```php
->byStatus(PaymentStatus $status)
->byMethod(PaymentMethod $method)
->posted()
```

### FeeStructure Scopes
```php
->active()
->forSession(int $sessionId)
->forCampus(int $campusId)
->forClass(int $classId)
->forSection(int $sectionId)
->default()
->effectiveOn($date)
```

### StudentDiscount Scopes
```php
->approved()
->pending()
->effectiveOn($date)
```

### FeeHead Scopes
```php
->active()
->byCategory(FeeHeadCategory $category)
->optional()
->recurring()
->ordered()
```

## Common Calculations

### Calculate Discount Amount
```php
if ($discount->value_type === ValueType::PERCENT) {
    $discountAmount = ($amount * $discount->value) / 100;
} else {
    $discountAmount = $discount->value;
}
```

### Calculate Fine
```php
$fineRule->calculateFine($voucherAmount, $daysLate);
// Handles grace days and fine type automatically
```

### Check if Item Applicable for Month
```php
$item->isApplicableForMonth($month);
// Handles year-spanning ranges (Aug-Jun)
```

## Voucher Number Format
```
FV-YYYY-NNNNNN
Example: FV-2024-000123
```

## Receipt Number Format
```
RCP-YYYY-NNNNNN
Example: RCP-2024-000456
```

## Important Indexes

### For Student Queries
- `student_id`
- `student_enrollment_record_id`

### For Period Queries
- `voucher_month + voucher_year`
- `payment_date`

### For Scope Queries
- `campus_id + session_id + class_id + section_id`

### For Status Queries
- `status`
- `approval_status`
- `is_active`

### For Date Queries
- `due_date`
- `effective_from + effective_to`

## Validation Rules Examples

### Voucher Generation
```php
'month' => 'required|integer|min:1|max:12',
'year' => 'required|integer|min:2020',
'campus_id' => 'required|exists:campuses,id',
'class_id' => 'nullable|exists:school_classes,id',
'section_id' => 'nullable|exists:sections,id',
```

### Payment Recording
```php
'student_id' => 'required|exists:students,id',
'payment_date' => 'required|date',
'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
'received_amount' => 'required|numeric|min:0',
'reference_no' => 'required_unless:payment_method,cash',
```

### Fee Structure
```php
'title' => 'required|string|max:200',
'session_id' => 'required|exists:academic_sessions,id',
'campus_id' => 'required|exists:campuses,id',
'effective_from' => 'required|date',
'status' => ['required', Rule::enum(FeeStructureStatus::class)],
```

## Common Pitfalls to Avoid

1. **Don't modify vouchers after generation** - They're historical snapshots
2. **Don't delete payments** - Use soft deletes and status changes
3. **Always use transactions** - For voucher generation and payment recording
4. **Check wallet balance** - Before applying advance adjustments
5. **Validate date ranges** - Ensure effective_from <= effective_to
6. **Use proper scopes** - Don't write raw WHERE clauses
7. **Eager load relationships** - Avoid N+1 queries
8. **Index your queries** - Use indexed columns in WHERE clauses

## Performance Tips

1. **Batch voucher generation** - Use chunks for large datasets
2. **Cache fee structures** - Active structures don't change often
3. **Use eager loading** - Load relationships upfront
4. **Index properly** - All foreign keys and status fields
5. **Use select()** - Only fetch needed columns
6. **Paginate results** - Don't load all vouchers at once
7. **Queue bulk operations** - Use Laravel queues for heavy tasks
8. **Database transactions** - Wrap related operations

## Testing Checklist

- [ ] Voucher generation for single student
- [ ] Voucher generation for entire class
- [ ] Payment recording (full payment)
- [ ] Payment recording (partial payment)
- [ ] Payment recording (excess payment to wallet)
- [ ] Payment allocation across multiple vouchers
- [ ] Discount application (percentage)
- [ ] Discount application (fixed amount)
- [ ] Fine calculation (fixed per day)
- [ ] Fine calculation (percentage)
- [ ] Advance payment to wallet
- [ ] Wallet deduction for voucher
- [ ] Fee structure priority (section > class > campus)
- [ ] Month range validation (Aug-Jun)
- [ ] Overdue voucher detection
- [ ] Print log tracking

## Quick Commands

```bash
# Run migrations
php artisan migrate

# Create seeder
php artisan make:seeder FeeHeadSeeder

# Run seeder
php artisan db:seed --class=FeeHeadSeeder

# Create controller
php artisan make:controller Fee/VoucherController

# Create service
php artisan make:class Services/Fee/VoucherGenerationService

# Create test
php artisan make:test Fee/VoucherGenerationTest

# Create form request
php artisan make:request Fee/StoreVoucherRequest
```

## Support

For detailed documentation, see:
- **FEE_MODULE_DOCUMENTATION.md** - Complete technical docs
- **FEE_MODULE_IMPLEMENTATION_GUIDE.md** - Step-by-step guide
- **FEE_MODULE_SUMMARY.md** - Executive summary

---

**Quick Tip**: Always use the provided enums instead of hardcoding strings. This ensures type safety and prevents typos.

**Example**: Use `VoucherStatus::PAID` instead of `'paid'`
