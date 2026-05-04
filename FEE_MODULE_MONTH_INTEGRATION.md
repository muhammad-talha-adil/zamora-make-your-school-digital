# Fee Module - Month Table Integration

## Overview

The fee module has been updated to use the existing `months` table instead of storing month numbers directly. This provides better data integrity and allows for easier localization and month name management.

## Changes Made

### Database Schema Changes

#### 1. fee_structure_items Table
**Before:**
- `billing_month` (tinyInteger) - Month number 1-12
- `starts_from_month` (tinyInteger) - Month number 1-12
- `ends_at_month` (tinyInteger) - Month number 1-12

**After:**
- `billing_month_id` (foreignId) - FK to months table
- `starts_from_month_id` (foreignId) - FK to months table
- `ends_at_month_id` (foreignId) - FK to months table

**Benefits:**
- Referential integrity enforced by foreign keys
- Month names stored centrally in months table
- Easy to add localized month names
- Prevents invalid month values

#### 2. fee_vouchers Table
**Before:**
- `voucher_month` (tinyInteger) - Month number 1-12

**After:**
- `voucher_month_id` (foreignId) - FK to months table

**Benefits:**
- Consistent month reference across system
- Can easily display month names in vouchers
- Better reporting capabilities

### Model Changes

#### FeeVoucher Model
**Added Relationships:**
```php
public function voucherMonth(): BelongsTo
{
    return $this->belongsTo(\App\Models\Month::class, 'voucher_month_id');
}
```

**Updated Fillable:**
- Changed `voucher_month` to `voucher_month_id`

**Updated Casts:**
- Changed `voucher_month` to `voucher_month_id`

**Updated Scopes:**
```php
// Before
public function scopeForPeriod($query, int $month, int $year)

// After
public function scopeForPeriod($query, int $monthId, int $year)
```

#### FeeStructureItem Model
**Added Relationships:**
```php
public function billingMonth(): BelongsTo
{
    return $this->belongsTo(\App\Models\Month::class, 'billing_month_id');
}

public function startsFromMonth(): BelongsTo
{
    return $this->belongsTo(\App\Models\Month::class, 'starts_from_month_id');
}

public function endsAtMonth(): BelongsTo
{
    return $this->belongsTo(\App\Models\Month::class, 'ends_at_month_id');
}
```

**Updated Fillable:**
- Changed `billing_month` to `billing_month_id`
- Changed `starts_from_month` to `starts_from_month_id`
- Changed `ends_at_month` to `ends_at_month_id`

**Updated Method:**
```php
// Before
public function isApplicableForMonth(int $month): bool

// After
public function isApplicableForMonth(int $monthNumber): bool
{
    // Now fetches month_number from related Month model
    $startsFromMonthNumber = $this->startsFromMonth?->month_number;
    $endsAtMonthNumber = $this->endsAtMonth?->month_number;
    // ... rest of logic
}
```

### Index Changes

**Updated Indexes:**
- `voucher_month + voucher_year` → `voucher_month_id + voucher_year`
- `billing_month` → `billing_month_id`

## Usage Examples

### Creating Fee Structure Items

**Before:**
```php
$structure->items()->create([
    'fee_head_id' => 1,
    'amount' => 5000,
    'frequency' => FeeFrequency::MONTHLY,
    'starts_from_month' => 8, // August
    'ends_at_month' => 6, // June
]);
```

**After:**
```php
// Get month IDs from months table
$augustId = Month::where('month_number', 8)->value('id');
$juneId = Month::where('month_number', 6)->value('id');

$structure->items()->create([
    'fee_head_id' => 1,
    'amount' => 5000,
    'frequency' => FeeFrequency::MONTHLY,
    'starts_from_month_id' => $augustId,
    'ends_at_month_id' => $juneId,
]);
```

### Creating Vouchers

**Before:**
```php
$voucher = FeeVoucher::create([
    'voucher_month' => 9, // September
    'voucher_year' => 2024,
    // ... other fields
]);
```

**After:**
```php
// Get month ID from months table
$septemberId = Month::where('month_number', 9)->value('id');

$voucher = FeeVoucher::create([
    'voucher_month_id' => $septemberId,
    'voucher_year' => 2024,
    // ... other fields
]);
```

### Querying Vouchers

**Before:**
```php
$vouchers = FeeVoucher::where('voucher_month', 9)
    ->where('voucher_year', 2024)
    ->get();
```

**After:**
```php
$septemberId = Month::where('month_number', 9)->value('id');

$vouchers = FeeVoucher::where('voucher_month_id', $septemberId)
    ->where('voucher_year', 2024)
    ->get();

// Or using scope
$vouchers = FeeVoucher::forPeriod($septemberId, 2024)->get();
```

### Displaying Month Names

**Before:**
```php
// Had to manually map month numbers to names
$monthNames = [
    1 => 'January', 2 => 'February', 3 => 'March',
    // ... etc
];
$monthName = $monthNames[$voucher->voucher_month];
```

**After:**
```php
// Automatically get month name from relationship
$monthName = $voucher->voucherMonth->name;
```

## Helper Methods

### Get Month ID by Number
```php
public static function getMonthIdByNumber(int $monthNumber): ?int
{
    return \App\Models\Month::where('month_number', $monthNumber)->value('id');
}
```

### Get Month Number by ID
```php
public static function getMonthNumberById(int $monthId): ?int
{
    return \App\Models\Month::where('id', $monthId)->value('month_number');
}
```

### Bulk Get Month IDs
```php
public static function getMonthIds(array $monthNumbers): array
{
    return \App\Models\Month::whereIn('month_number', $monthNumbers)
        ->pluck('id', 'month_number')
        ->toArray();
}
```

## Migration Strategy

If you have existing data with month numbers, create a migration to convert them:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Get month mappings
        $months = DB::table('months')
            ->pluck('id', 'month_number')
            ->toArray();
        
        // Update fee_structure_items
        DB::table('fee_structure_items')->get()->each(function ($item) use ($months) {
            $updates = [];
            
            if ($item->billing_month_id) {
                $updates['billing_month_id'] = $months[$item->billing_month] ?? null;
            }
            if ($item->starts_from_month_id) {
                $updates['starts_from_month_id'] = $months[$item->starts_from_month] ?? null;
            }
            if ($item->ends_at_month_id) {
                $updates['ends_at_month_id'] = $months[$item->ends_at_month] ?? null;
            }
            
            if (!empty($updates)) {
                DB::table('fee_structure_items')
                    ->where('id', $item->id)
                    ->update($updates);
            }
        });
        
        // Update fee_vouchers
        DB::table('fee_vouchers')->get()->each(function ($voucher) use ($months) {
            if ($voucher->voucher_month) {
                DB::table('fee_vouchers')
                    ->where('id', $voucher->id)
                    ->update([
                        'voucher_month_id' => $months[$voucher->voucher_month] ?? null
                    ]);
            }
        });
    }
    
    public function down(): void
    {
        // Reverse the migration if needed
    }
};
```

## Seeding Months Table

Ensure your months table is properly seeded:

```php
<?php

namespace Database\Seeders;

use App\Models\Month;
use Illuminate\Database\Seeder;

class MonthSeeder extends Seeder
{
    public function run(): void
    {
        $months = [
            ['name' => 'January', 'month_number' => 1],
            ['name' => 'February', 'month_number' => 2],
            ['name' => 'March', 'month_number' => 3],
            ['name' => 'April', 'month_number' => 4],
            ['name' => 'May', 'month_number' => 5],
            ['name' => 'June', 'month_number' => 6],
            ['name' => 'July', 'month_number' => 7],
            ['name' => 'August', 'month_number' => 8],
            ['name' => 'September', 'month_number' => 9],
            ['name' => 'October', 'month_number' => 10],
            ['name' => 'November', 'month_number' => 11],
            ['name' => 'December', 'month_number' => 12],
        ];

        foreach ($months as $month) {
            Month::firstOrCreate(
                ['month_number' => $month['month_number']],
                ['name' => $month['name']]
            );
        }
    }
}
```

Run the seeder:
```bash
php artisan db:seed --class=MonthSeeder
```

## Benefits of This Change

1. **Data Integrity**: Foreign key constraints prevent invalid month references
2. **Centralized Management**: Month names stored in one place
3. **Localization Ready**: Easy to add localized month names (e.g., Urdu names)
4. **Better Reporting**: Can join with months table for readable reports
5. **Consistency**: Same month reference pattern across entire system
6. **Flexibility**: Can add additional month metadata (fiscal year, quarter, etc.)

## Localization Example

You can extend the months table for Urdu names:

```php
Schema::table('months', function (Blueprint $table) {
    $table->string('name_urdu')->nullable()->after('name');
});

// Seed Urdu names
$urduMonths = [
    1 => 'جنوری',
    2 => 'فروری',
    3 => 'مارچ',
    // ... etc
];

foreach ($urduMonths as $number => $urduName) {
    Month::where('month_number', $number)->update(['name_urdu' => $urduName]);
}
```

## Backward Compatibility

If you need to maintain backward compatibility temporarily:

```php
// Add accessor to FeeVoucher model
public function getVoucherMonthAttribute(): int
{
    return $this->voucherMonth?->month_number ?? 0;
}

// Add accessor to FeeStructureItem model
public function getBillingMonthAttribute(): ?int
{
    return $this->billingMonth?->month_number;
}
```

This allows old code using `$voucher->voucher_month` to still work while you migrate.

## Testing

Update your tests to use month IDs:

```php
public function test_voucher_generation()
{
    $septemberId = Month::where('month_number', 9)->value('id');
    
    $voucher = FeeVoucher::factory()->create([
        'voucher_month_id' => $septemberId,
        'voucher_year' => 2024,
    ]);
    
    $this->assertEquals('September', $voucher->voucherMonth->name);
}
```

## Conclusion

This integration with the months table provides better data integrity, easier maintenance, and sets the foundation for future enhancements like localization and fiscal year management.
