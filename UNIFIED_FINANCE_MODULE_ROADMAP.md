# Unified Finance Module - Complete Roadmap

## Table of Contents
1. [Introduction](#introduction)
2. [Current State Analysis](#current-state-analysis)
3. [What to Remove](#what-to-remove)
4. [What to Add (New Tables)](#what-to-add-new-tables)
5. [Menu Structure](#menu-structure)
6. [How It Works - Real World Examples](#how-it-works---real-world-examples)
7. [Technical Implementation](#technical-implementation)
8. [Integration Points](#integration-points)
9. [Future Extensibility](#future-extensibility)

---

## Introduction

Currently, your school management system has **separate** payment systems:
- **Fee Module**: Handles student fee payments (INCOME)
- **Inventory Module**: Handles supplier payments (EXPENSE)

Both use **different tables** and there's **no unified view** of finances.

This roadmap explains how to create a **UNIFIED Finance Module** that:
- Tracks ALL money coming in (income)
- Tracks ALL money going out (expense)
- Works for Fee, Inventory, and future modules
- Provides complete financial reports

---

## Current State Analysis

### Current Tables (Fee Module)

| Table Name | Model | Description |
|------------|-------|-------------|
| `fee_heads` | `App\Models\Fee\FeeHead` | Fee types (Tuition, Transport, etc.) |
| `fee_structures` | `App\Models\Fee\FeeStructure` | Fee structure definitions |
| `fee_structure_items` | `App\Models\Fee\FeeStructureItem` | Individual fee items in structure |
| `fee_vouchers` | `App\Models\Fee\FeeVoucher` | Student fee invoices/challans |
| `fee_voucher_items` | `App\Models\Fee\FeeVoucherItem` | Individual fee items on voucher |
| `fee_voucher_adjustments` | `App\Models\Fee\FeeVoucherAdjustment` | Discounts/fines applied |
| `fee_payments` | `App\Models\Fee\FeePayment` | **Student payments (INCOME)** |
| `fee_payment_allocations` | `App\Models\Fee\FeePaymentAllocation` | How payments applied to vouchers |
| `fee_voucher_print_logs` | `App\Models\Fee\FeeVoucherPrintLog` | Print history |

### Current Tables (Inventory Module)

| Table Name | Model | Description |
|------------|-------|-------------|
| `inventory_items` | `App\Models\InventoryItem` | Inventory products |
| `inventory_stocks` | `App\Models\InventoryStock` | Stock quantities |
| `inventory_types` | `App\Models\InventoryType` | Item categories |
| `inventory_purchases` | `App\Models\Purchase` | **Purchase orders (EXPENSE)** |
| `inventory_purchase_items` | `App\Models\PurchaseItem` | Items in purchase |
| `purchase_payments` | `App\Models\PurchasePayment` | **Supplier payments** |
| `suppliers` | `App\Models\Supplier` | Supplier information |

### Current Relationships (Fee Module)

```php
// FeeVoucher Model Relationships
App\Models\Fee\FeeVoucher
├── student()           → BelongsTo(Student::class)
├── campus()            → BelongsTo(Campus::class)
├── schoolClass()       → BelongsTo(SchoolClass::class)
├── section()           → BelongsTo(Section::class)
├── voucherMonth()      → BelongsTo(Month::class)
├── items()             → HasMany(FeeVoucherItem::class)
├── adjustments()       → HasMany(FeeVoucherAdjustment::class)
├── paymentAllocations()→ HasMany(FeePaymentAllocation::class)
└── payments()          → HasManyThrough(FeePayment::class, FeePaymentAllocation::class)

// FeePayment Model Relationships
App\Models\Fee\FeePayment
├── student()           → BelongsTo(Student::class)
├── campus()            → BelongsTo(Campus::class)
├── enrollmentRecord()  → BelongsTo(StudentEnrollmentRecord::class)
├── receiver()          → BelongsTo(User::class, 'received_by')
└── allocations()       → HasMany(FeePaymentAllocation::class)
```

### Current Relationships (Inventory Module)

```php
// Purchase Model Relationships
App\Models\Purchase
├── campus()            → BelongsTo(Campus::class)
├── supplier()          → BelongsTo(Supplier::class)
├── purchaseItems()     → HasMany(PurchaseItem::class)
└── payments()          → HasMany(PurchasePayment::class)

// PurchasePayment Model Relationships
App\Models\PurchasePayment
├── campus()            → BelongsTo(Campus::class)
├── supplier()          → BelongsTo(Supplier::class)
└── purchase()          → BelongsTo(Purchase::class)
```

### Current Menu Structure (from MenuSeeder.php)

```php
// Fee Management Menu
Fee Management (ID: 5, Icon: banknote)
├── Dashboard        → /fee/dashboard         → FeeDashboardController@index
├── Fee Structures   → /fee/structures        → FeeStructureController@index
├── Vouchers         → /fee/vouchers          → FeeVoucherController@index
├── Payments         → /fee/payments           → FeePaymentController@index
├── Reports          → /fee/reports           → FeeReportController@index
└── Settings         → /fee/settings          → FeeSettingsController@index

// Inventory Menu
Inventory (ID: 6, Icon: package)
├── Dashboard         → /inventory             → InventoryController@index
├── Items & Stock     → /inventory/items-stock → InventoryStocksController@index
├── Purchases         → /inventory/purchases   → PurchasesController@index
└── Student Inventory → /inventory/student    → StudentInventoriesController@index
```

### Current Controllers

#### Fee Controllers
| Controller | File Path | Purpose |
|------------|-----------|---------|
| FeeDashboardController | `app/Http/Controllers/Fee/FeeDashboardController.php` | Dashboard |
| FeeHeadController | `app/Http/Controllers/Fee/FeeHeadController.php` | Manage fee heads |
| FeeStructureController | `app/Http/Controllers/Fee/FeeStructureController.php` | Fee structures |
| FeeStructureItemController | `app/Http/Controllers/Fee/FeeStructureItemController.php` | Structure items |
| FeeVoucherController | `app/Http/Controllers/Fee/FeeVoucherController.php` | Vouchers CRUD |
| FeePaymentController | `app/Http/Controllers/Fee/FeePaymentController.php` | **Payments CRUD** |
| FeeReportController | `app/Http/Controllers/Fee/FeeReportController.php` | Reports |
| FeeSettingsController | `app/Http/Controllers/Fee/FeeSettingsController.php` | Settings |

#### Inventory Controllers
| Controller | File Path | Purpose |
|------------|-----------|---------|
| InventoryItemsController | `app/Http/Controllers/Inventory/InventoryItemsController.php` | Items |
| InventoryStocksController | `app/Http/Controllers/Inventory/InventoryStocksController.php` | Stock |
| PurchasesController | `app/Http/Controllers/Inventory/PurchasesController.php` | Purchases |
| PurchaseReturnsController | `app/Http/Controllers/Inventory/PurchaseReturnsController.php` | Returns |
| SupplierController | `app/Http/Controllers/Inventory/SuppliersController.php` | Suppliers |

---

## What to Remove

Since your database is **EMPTY** (system in development), you can remove these tables completely:

### Tables to DELETE:
| Old Table | Model | Reason |
|-----------|-------|--------|
| `fee_payments` | `App\Models\Fee\FeePayment` | Will be replaced by unified `ledgers` table |
| `purchase_payments` | `App\Models\PurchasePayment` | Will be replaced by unified `ledgers` table |

### Keep These Tables (They serve BUSINESS purposes):
| Table | Model | Purpose | Why Keep |
|-------|-------|---------|----------|
| `fee_vouchers` | `App\Models\Fee\FeeVoucher` | Student invoices | Tracks WHAT student owes |
| `fee_voucher_items` | `App\Models\Fee\FeeVoucherItem` | Individual fee items | Details of charges |
| `fee_voucher_adjustments` | `App\Models\Fee\FeeVoucherAdjustment` | Discounts/fines | Charge adjustments |
| `inventory_purchases` | `App\Models\Purchase` | Supplier bills | Tracks WHAT we owe suppliers |
| `inventory_purchase_items` | `App\Models\PurchaseItem` | Purchase details | Details of purchased items |

---

## What to Add (New Tables)

### 1. `ledgers` - Main Transaction Table

**Migration File:** `database/migrations/2026_xx_xx_xxxxxx_create_ledgers_table.php`

**Model:** `App\Models\Ledger\Ledger.php`

**Table Structure:**
| Column | Type | Constraints | Purpose |
|--------|------|-------------|---------|
| `id` | bigint | PK, auto-increment | Primary key |
| `ledger_number` | varchar(50) | Unique | Unique ID (LG-2026-0001) |
| `transaction_type` | enum | INCOME, EXPENSE | Money in or out |
| `transaction_date` | date | Not null | When transaction happened |
| `amount` | decimal(10,2) | Not null | How much |
| `description` | text | Nullable | Notes |
| `reference_type` | varchar(255) | Nullable | Polymorphic - links to source |
| `reference_id` | bigint | Nullable | ID of the source record |
| `category_id` | bigint | FK | Link to ledger_categories |
| `payment_method` | varchar(50) | Nullable | Cash, Bank, JazzCash, etc. |
| `reference_number` | varchar(100) | Nullable | Bank reference, cheque # |
| `campus_id` | bigint | FK | Which campus |
| `student_id` | bigint | FK, Nullable | Student (for income) |
| `supplier_id` | bigint | FK, Nullable | Supplier (for expense) |
| `created_by` | bigint | FK | User who created |
| `created_at` | timestamp | Auto | Auto |
| `updated_at` | timestamp | Auto | Auto |

### 2. `ledger_categories` - Categories Table

**Migration File:** `database/migrations/2026_xx_xx_xxxxxx_create_ledger_categories_table.php`

**Model:** `App\Models\Ledger\LedgerCategory.php`

**Table Structure:**
| Column | Type | Constraints | Purpose |
|--------|------|-------------|---------|
| `id` | bigint | PK, auto-increment | Primary key |
| `name` | varchar(100) | Not null | Category name |
| `type` | enum | INCOME, EXPENSE | Category type |
| `parent_id` | bigint | FK, Nullable | For sub-categories |
| `is_active` | tinyint | Default 1 | Enable/disable |
| `created_at` | timestamp | Auto | Auto |
| `updated_at` | timestamp | Auto | Auto |

**Default Categories to Seed:**

*Income Categories:*
| ID | Name | Type |
|----|------|------|
| 1 | Tuition Fee | INCOME |
| 2 | Admission Fee | INCOME |
| 3 | Transport Fee | INCOME |
| 4 | Book Sales | INCOME |
| 5 | Uniform Fee | INCOME |
| 6 | Exam Fee | INCOME |
| 7 | Registration Fee | INCOME |
| 8 | Late Fine | INCOME |
| 9 | Other Income | INCOME |

*Expense Categories:*
| ID | Name | Type |
|----|------|------|
| 10 | Supplier Payment (Books) | EXPENSE |
| 11 | Supplier Payment (Stationery) | EXPENSE |
| 12 | Supplier Payment (Uniform) | EXPENSE |
| 13 | Salary Expense | EXPENSE |
| 14 | Rent Expense | EXPENSE |
| 15 | Electricity Expense | EXPENSE |
| 16 | Internet Expense | EXPENSE |
| 17 | Transport Expense | EXPENSE |
| 18 | Maintenance Expense | EXPENSE |
| 19 | Other Expense | EXPENSE |

### 3. `payment_methods` - Payment Methods Table

**Migration File:** `database/migrations/2026_xx_xx_xxxxxx_create_payment_methods_table.php`

**Model:** `App\Models\Ledger\PaymentMethod.php`

**Table Structure:**
| Column | Type | Constraints | Purpose |
|--------|------|-------------|---------|
| `id` | bigint | PK, auto-increment | Primary key |
| `name` | varchar(50) | Not null | Method name |
| `code` | varchar(20) | Unique | Short code (cash, bank) |
| `is_active` | tinyint | Default 1 | Enable/disable |
| `created_at` | timestamp | Auto | Auto |
| `updated_at` | timestamp | Auto | Auto |

**Default Payment Methods to Seed:**
| ID | Name | Code |
|----|------|------|
| 1 | Cash | cash |
| 2 | Bank Transfer | bank |
| 3 | JazzCash | jazzcash |
| 4 | EasyPaisa | easypaisa |
| 5 | Cheque | cheque |
| 6 | Online Payment | online |

---

## Menu Structure

### New "Finance" Main Menu

Update `database/seeders/MenuSeeder.php` to add:

```php
// ==================== FINANCE MENU ====================
$finance = Menu::create([
    'title' => 'Finance',
    'icon' => 'wallet',
    'type' => 'main',
    'order' => 7,
    'is_active' => true,
]);

// Finance submenus
Menu::create([
    'title' => 'Dashboard',
    'icon' => 'layout-dashboard',
    'type' => 'main',
    'order' => 1,
    'parent_id' => $finance->id,
    'is_active' => true,
    'url' => '/finance',
]);

Menu::create([
    'title' => 'Transactions',
    'icon' => 'list',
    'type' => 'main',
    'order' => 2,
    'parent_id' => $finance->id,
    'is_active' => true,
    'url' => '/finance/transactions',
]);

Menu::create([
    'title' => 'Receive Payment',
    'icon' => 'arrow-down-circle',
    'type' => 'main',
    'order' => 3,
    'parent_id' => $finance->id,
    'is_active' => true,
    'url' => '/finance/receive-payment',
]);

Menu::create([
    'title' => 'Make Payment',
    'icon' => 'arrow-up-circle',
    'type' => 'main',
    'order' => 4,
    'parent_id' => $finance->id,
    'is_active' => true,
    'url' => '/finance/make-payment',
]);

Menu::create([
    'title' => 'Categories',
    'icon' => 'folder',
    'type' => 'main',
    'order' => 5,
    'parent_id' => $finance->id,
    'is_active' => true,
    'url' => '/finance/categories',
]);

Menu::create([
    'title' => 'Payment Methods',
    'icon' => 'credit-card',
    'type' => 'main',
    'order' => 6,
    'parent_id' => $finance->id,
    'is_active' => true,
    'url' => '/finance/payment-methods',
]);

$financeReports = Menu::create([
    'title' => 'Reports',
    'icon' => 'bar-chart',
    'type' => 'main',
    'order' => 7,
    'parent_id' => $finance->id,
    'is_active' => true,
]);

Menu::create([
    'title' => 'Daily Cash Book',
    'icon' => 'book',
    'type' => 'main',
    'order' => 1,
    'parent_id' => $financeReports->id,
    'is_active' => true,
    'url' => '/finance/reports/cash-book',
]);

Menu::create([
    'title' => 'Income Statement',
    'icon' => 'trending-up',
    'type' => 'main',
    'order' => 2,
    'parent_id' => $financeReports->id,
    'is_active' => true,
    'url' => '/finance/reports/income',
]);

Menu::create([
    'title' => 'Expense Statement',
    'icon' => 'trending-down',
    'type' => 'main',
    'order' => 3,
    'parent_id' => $financeReports->id,
    'is_active' => true,
    'url' => '/finance/reports/expense',
]);
```

### Route Structure (routes/finance.php)

```php
<?php

use App\Http\Controllers\Finance\FinanceController;
use App\Http\Controllers\Finance\TransactionController;
use App\Http\Controllers\Finance\ReceivePaymentController;
use App\Http\Controllers\Finance\MakePaymentController;
use App\Http\Controllers\Finance\CategoryController;
use App\Http\Controllers\Finance\PaymentMethodController;
use App\Http\Controllers\Finance\ReportController;
use Illuminate\Support\Facades\Route;

$middleware = ['web', 'auth'];

Route::prefix('finance')->name('finance.')->middleware($middleware)->group(function () {

    // Dashboard
    Route::get('/', [FinanceController::class, 'index'])->name('dashboard');
    
    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{ledger}', [TransactionController::class, 'show'])->name('transactions.show');
    
    // Receive Payment (Income)
    Route::get('/receive-payment', [ReceivePaymentController::class, 'create'])->name('receive.create');
    Route::post('/receive-payment', [ReceivePaymentController::class, 'store'])->name('receive.store');
    
    // Make Payment (Expense)
    Route::get('/make-payment', [MakePaymentController::class, 'create'])->name('make.create');
    Route::post('/make-payment', [MakePaymentController::class, 'store'])->name('make.store');
    
    // Categories
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    
    // Payment Methods
    Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index');
    Route::post('/payment-methods', [PaymentMethodController::class, 'store'])->name('payment-methods.store');
    Route::put('/payment-methods/{method}', [PaymentMethodController::class, 'update'])->name('payment-methods.update');
    
    // Reports
    Route::get('/reports/cash-book', [ReportController::class, 'cashBook'])->name('reports.cash-book');
    Route::get('/reports/income', [ReportController::class, 'income'])->name('reports.income');
    Route::get('/reports/expense', [ReportController::class, 'expense'])->name('reports.expense');
});
```

---

## How It Works - Real World Examples

### Example 1: Student "Ahmed" Pays Tuition Fee

**Scenario:** Ahmed comes to pay his fee for March 2026

**Step 1: Admin opens Receive Payment**
```
URL: /finance/receive-payment
Controller: ReceivePaymentController@create

Screen shows:
- Select Student: [Ahmed - Class 5-A - Reg# 2024-001]
- Pending Vouchers:
  - Voucher #FV-2026-0015 - Rs. 10,000 (Due: 10-March)
  - Voucher #FV-2026-0016 - Rs. 5,000 (Due: 15-April)
- Total Pending: Rs. 15,000
- Previous Paid: Rs. 0
- Balance: Rs. 15,000
```

**Step 2: Ahmed pays Rs. 10,000 in Cash**
```
Form fields:
- Student: Ahmed (ID: 101)
- Amount: 10000
- Payment Method: Cash (ID: 1)
- Category: Tuition Fee (ID: 1)
- Note: March 2026 fee
- Voucher: FV-2026-0015
```

**Step 3: What gets saved:**

**A. In `fee_vouchers` table (Business - unchanged):**
```php
// App\Models\Fee\FeeVoucher (ID: 15)
$voucher = FeeVoucher::find(15);
$voucher->paid_amount = 10000;      // updated from 0
$voucher->balance_amount = 0;       // updated from 10000
$voucher->status = 'paid';           // updated from 'unpaid'
$voucher->save();
```

**B. In `ledgers` table (Finance - NEW):**
```php
// App\Models\Ledger\Ledger
$ledger = Ledger::create([
    'ledger_number' => 'LG-2026-0001',
    'transaction_type' => 'INCOME',
    'transaction_date' => '2026-03-22',
    'amount' => 10000,
    'description' => 'March 2026 tuition fee for Ahmed',
    'reference_type' => 'App\\Models\\Fee\\FeeVoucher',
    'reference_id' => 15,
    'category_id' => 1,           // Tuition Fee
    'payment_method' => 'cash',
    'student_id' => 101,          // Ahmed
    'campus_id' => 1,
    'created_by' => auth()->id(),
]);
```

---

### Example 2: Pay Supplier "ABC Books"

**Scenario:** School pays ABC Books supplier for books purchased

**Step 1: Admin opens Make Payment**
```
URL: /finance/make-payment
Controller: MakePaymentController@create

Screen shows:
- Select Purchase: [PR-2026-0012 - ABC Books - Rs. 8,000]
- Supplier: ABC Books
- Amount Due: Rs. 8,000
- Amount to Pay: [________]
```

**Step 2: Pay Rs. 8,000 via Bank Transfer**
```
Form fields:
- Purchase: PR-2026-0012 (ID: 12)
- Amount: 8000
- Payment Method: Bank Transfer (ID: 2)
- Category: Supplier Payment (Books) (ID: 10)
- Reference Number: TXN-123456
- Note: Payment for PR-2026-0012
```

**Step 3: What gets saved:**

**A. In `inventory_purchases` table (Business - unchanged):**
```php
// App\Models\Purchase (ID: 12)
$purchase = Purchase::find(12);
$purchase->paid_amount = 8000;
$purchase->payment_status = 'paid';
$purchase->save();
```

**B. In `ledgers` table (Finance - NEW):**
```php
// App\Models\Ledger\Ledger
$ledger = Ledger::create([
    'ledger_number' => 'LG-2026-0002',
    'transaction_type' => 'EXPENSE',
    'transaction_date' => '2026-03-22',
    'amount' => 8000,
    'description' => 'Payment for PR-2026-0012',
    'reference_type' => 'App\\Models\\Purchase',
    'reference_id' => 12,
    'category_id' => 10,           // Supplier Payment (Books)
    'payment_method' => 'bank_transfer',
    'reference_number' => 'TXN-123456',
    'supplier_id' => 5,           // ABC Books
    'campus_id' => 1,
    'created_by' => auth()->id(),
]);
```

---

## Technical Implementation

### New Directory Structure

```
app/
├── Enums/
│   └── Ledger/
│       └── TransactionType.php          # INCOME, EXPENSE
│
├── Models/
│   ├── Fee/
│   │   └── FeeVoucher.php              # Existing - modified for ledger
│   │
│   ├── Purchase.php                     # Existing - modified for ledger
│   │
│   └── Ledger/                          # NEW - Finance Module
│       ├── Ledger.php
│       ├── LedgerCategory.php
│       └── PaymentMethod.php
│
├── Http/
│   └── Controllers/
│       ├── Fee/
│       │   └── FeePaymentController.php  # Modified to use FinanceService
│       │
│       ├── Inventory/
│       │   └── PurchasesController.php   # Modified to use FinanceService
│       │
│       └── Finance/                        # NEW
│           ├── FinanceController.php
│           ├── TransactionController.php
│           ├── ReceivePaymentController.php
│           ├── MakePaymentController.php
│           ├── CategoryController.php
│           ├── PaymentMethodController.php
│           └── ReportController.php
│
├── Services/
│   └── FinanceService.php               # NEW - Central payment handling
│
├── Resources/
│   └── Views/
│       └── finance/                      # NEW
│           ├── transactions/
│           │   ├── index.blade.php
│           │   └── show.blade.php
│           ├── payments/
│           │   ├── receive.blade.php
│           │   └── make.blade.php
│           └── reports/
│               ├── cash-book.blade.php
│               ├── income.blade.php
│               └── expense.blade.php
│
routes/
├── fee.php                              # Modified
├── finance.php                          # NEW
└── inventory.php                        # Modified
│
database/
└── migrations/
    ├── 2026_xx_xx_xxxxxx_create_ledgers_table.php           # NEW
    ├── 2026_xx_xx_xxxxxx_create_ledger_categories_table.php   # NEW
    └── 2026_xx_xx_xxxxxx_create_payment_methods_table.php    # NEW
│
resources/
└── js/
    └── pages/
        └── Finance/                     # NEW - Vue pages
            ├── Dashboard.vue
            ├── Transactions.vue
            ├── ReceivePayment.vue
            ├── MakePayment.vue
            ├── Categories.vue
            └── PaymentMethods.vue
```

### New Models

#### App\Models\Ledger\Ledger.php
```php
<?php

namespace App\Models\Ledger;

use App\Models\Campus;
use App\Models\User;
use App\Models\Student;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Ledger extends Model
{
    protected $table = 'ledgers';

    protected $fillable = [
        'ledger_number',
        'transaction_type',
        'transaction_date',
        'amount',
        'description',
        'reference_type',
        'reference_id',
        'category_id',
        'payment_method',
        'reference_number',
        'campus_id',
        'student_id',
        'supplier_id',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];

    // Polymorphic relationship - links to any table
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(LedgerCategory::class, 'category_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeIncome($query)
    {
        return $query->where('transaction_type', 'INCOME');
    }

    public function scopeExpense($query)
    {
        return $query->where('transaction_type', 'EXPENSE');
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('transaction_date', $date);
    }

    public function scopeForCampus($query, $campusId)
    {
        return $query->where('campus_id', $campusId);
    }
}
```

#### App\Models\Ledger\LedgerCategory.php
```php
<?php

namespace App\Models\Ledger;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerCategory extends Model
{
    protected $table = 'ledger_categories';

    protected $fillable = [
        'name',
        'type',
        'parent_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(LedgerCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(LedgerCategory::class, 'parent_id');
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(Ledger::class, 'category_id');
    }

    public function isIncome(): bool
    {
        return $this->type === 'INCOME';
    }

    public function isExpense(): bool
    {
        return $this->type === 'EXPENSE';
    }
}
```

#### App\Models\Ledger\PaymentMethod.php
```php
<?php

namespace App\Models\Ledger;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    protected $table = 'payment_methods';

    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function ledgers(): HasMany
    {
        return $this->hasMany(Ledger::class, 'payment_method', 'code');
    }
}
```

### New Service

#### App\Services\FinanceService.php
```php
<?php

namespace App\Services;

use App\Models\Ledger\Ledger;
use App\Models\Ledger\LedgerCategory;
use App\Models\Ledger\PaymentMethod;
use Illuminate\Support\Str;

class FinanceService
{
    /**
     * Create an income transaction (money received)
     */
    public function createIncomeTransaction(array $data): Ledger
    {
        $ledgerNumber = $this->generateLedgerNumber('INCOME');
        
        return Ledger::create([
            'ledger_number' => $ledgerNumber,
            'transaction_type' => 'INCOME',
            'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
            'amount' => $data['amount'],
            'description' => $data['description'] ?? null,
            'reference_type' => $data['reference_type'],
            'reference_id' => $data['reference_id'],
            'category_id' => $data['category_id'],
            'payment_method' => $data['payment_method'],
            'reference_number' => $data['reference_number'] ?? null,
            'campus_id' => $data['campus_id'],
            'student_id' => $data['student_id'] ?? null,
            'supplier_id' => null, // Always null for income
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]);
    }

    /**
     * Create an expense transaction (money paid)
     */
    public function createExpenseTransaction(array $data): Ledger
    {
        $ledgerNumber = $this->generateLedgerNumber('EXPENSE');
        
        return Ledger::create([
            'ledger_number' => $ledgerNumber,
            'transaction_type' => 'EXPENSE',
            'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
            'amount' => $data['amount'],
            'description' => $data['description'] ?? null,
            'reference_type' => $data['reference_type'],
            'reference_id' => $data['reference_id'],
            'category_id' => $data['category_id'],
            'payment_method' => $data['payment_method'],
            'reference_number' => $data['reference_number'] ?? null,
            'campus_id' => $data['campus_id'],
            'student_id' => null, // Always null for expense
            'supplier_id' => $data['supplier_id'] ?? null,
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]);
    }

    /**
     * Generate unique ledger number
     */
    protected function generateLedgerNumber(string $type): string
    {
        $prefix = $type === 'INCOME' ? 'LI' : 'LE';
        $year = date('Y');
        $lastLedger = Ledger::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastLedger && preg_match('/' . $prefix . '-' . $year . '-(\d+)/', $lastLedger->ledger_number, $matches)) {
            $counter = intval($matches[1]) + 1;
        } else {
            $counter = 1;
        }
        
        return $prefix . '-' . $year . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get total income for a period
     */
    public function getTotalIncome($fromDate, $toDate, $campusId = null): float
    {
        $query = Ledger::income()
            ->whereBetween('transaction_date', [$fromDate, $toDate]);
        
        if ($campusId) {
            $query->where('campus_id', $campusId);
        }
        
        return $query->sum('amount');
    }

    /**
     * Get total expense for a period
     */
    public function getTotalExpense($fromDate, $toDate, $campusId = null): float
    {
        $query = Ledger::expense()
            ->whereBetween('transaction_date', [$fromDate, $toDate]);
        
        if ($campusId) {
            $query->where('campus_id', $campusId);
        }
        
        return $query->sum('amount');
    }

    /**
     * Get balance (income - expense)
     */
    public function getBalance($fromDate, $toDate, $campusId = null): float
    {
        $income = $this->getTotalIncome($fromDate, $toDate, $campusId);
        $expense = $this->getTotalExpense($fromDate, $toDate, $campusId);
        
        return $income - $expense;
    }
}
```

### New Controllers

#### App\Http\Controllers\Finance\FinanceController.php
```php
<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\FinanceService;
use App\Models\Campus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FinanceController extends Controller
{
    protected $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    public function index(Request $request)
    {
        $campusId = $request->filled('campus_id') 
            ? $request->campus_id 
            : auth()->user()->campus_id;

        $today = now()->toDateString();
        
        $data = [
            'today_income' => $this->financeService->getTotalIncome($today, $today, $campusId),
            'today_expense' => $this->financeService->getTotalExpense($today, $today, $campusId),
            'today_balance' => $this->financeService->getBalance($today, $today, $campusId),
            'month_income' => $this->financeService->getTotalIncome(
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
                $campusId
            ),
            'month_expense' => $this->financeService->getTotalExpense(
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
                $campusId
            ),
            'campuses' => Campus::select('id', 'name')->where('is_active', true)->get(),
            'selected_campus' => $campusId,
        ];

        return Inertia::render('Finance/Dashboard', $data);
    }
}
```

#### App\Http\Controllers\Finance\TransactionController.php
```php
<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Ledger\Ledger;
use App\Models\Campus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Ledger::with(['category', 'campus', 'student', 'supplier']);

        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('date_from')) {
            $query->where('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('transaction_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('ledger_number', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(50);

        return Inertia::render('Finance/Transactions', [
            'transactions' => $transactions,
            'filters' => $request->only(['campus_id', 'type', 'category_id', 'date_from', 'date_to', 'search']),
        ]);
    }
}
```

#### App\Http\Controllers\Finance\ReceivePaymentController.php
```php
<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\FinanceService;
use App\Models\Fee\FeeVoucher;
use App\Models\Campus;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReceivePaymentController extends Controller
{
    protected $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    public function create(Request $request)
    {
        $students = [];
        
        if ($request->filled('search')) {
            $students = Student::where('name', 'like', '%' . $request->search . '%')
                ->orWhere('registration_number', 'like', '%' . $request->search . '%')
                ->limit(10)
                ->get(['id', 'name', 'registration_number', 'campus_id', 'class_id']);
        }

        return Inertia::render('Finance/ReceivePayment', [
            'campuses' => Campus::select('id', 'name')->where('is_active', true)->get(),
            'students' => $students,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'voucher_id' => 'required|exists:fee_vouchers,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required',
            'category_id' => 'required|exists:ledger_categories,id',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $voucher = FeeVoucher::findOrFail($validated['voucher_id']);
        
        // Update voucher (existing business logic)
        $voucher->paid_amount += $validated['amount'];
        $voucher->balance_amount = max(0, $voucher->net_amount - $voucher->paid_amount);
        $voucher->save();

        // Create ledger entry (NEW unified finance)
        $this->financeService->createIncomeTransaction([
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'category_id' => $validated['category_id'],
            'student_id' => $validated['student_id'],
            'reference_type' => 'App\\Models\\Fee\\FeeVoucher',
            'reference_id' => $validated['voucher_id'],
            'transaction_date' => $validated['transaction_date'],
            'description' => $validated['description'],
            'campus_id' => $voucher->campus_id,
        ]);

        return redirect()->route('finance.transactions.index')
            ->with('success', 'Payment received successfully!');
    }
}
```

#### App\Http\Controllers\Finance\MakePaymentController.php
```php
<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\FinanceService;
use App\Models\Purchase;
use App\Models\Campus;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MakePaymentController extends Controller
{
    protected $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    public function create(Request $request)
    {
        $purchases = [];
        $suppliers = [];

        if ($request->filled('search')) {
            // Search purchases
            $purchases = Purchase::with(['supplier', 'campus'])
                ->where('payment_status', '!=', 'paid')
                ->where('purchase_id', 'like', '%' . $request->search . '%')
                ->limit(10)
                ->get();
            
            // Or search suppliers
            $suppliers = Supplier::where('name', 'like', '%' . $request->search . '%')
                ->limit(10)
                ->get(['id', 'name', 'email', 'phone']);
        }

        return Inertia::render('Finance/MakePayment', [
            'campuses' => Campus::select('id', 'name')->where('is_active', true)->get(),
            'purchases' => $purchases,
            'suppliers' => $suppliers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_id' => 'required|exists:inventory_purchases,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required',
            'category_id' => 'required|exists:ledger_categories,id',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $purchase = Purchase::findOrFail($validated['purchase_id']);
        
        // Update purchase (existing business logic)
        $purchase->paid_amount += $validated['amount'];
        $purchase->payment_status = $purchase->paid_amount >= $purchase->total_amount 
            ? 'paid' 
            : 'partial';
        $purchase->save();

        // Create ledger entry (NEW unified finance)
        $this->financeService->createExpenseTransaction([
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'category_id' => $validated['category_id'],
            'supplier_id' => $purchase->supplier_id,
            'reference_type' => 'App\\Models\\Purchase',
            'reference_id' => $validated['purchase_id'],
            'transaction_date' => $validated['transaction_date'],
            'description' => $validated['description'],
            'campus_id' => $purchase->campus_id,
        ]);

        return redirect()->route('finance.transactions.index')
            ->with('success', 'Payment made successfully!');
    }
}
```

---

## Integration Points

### 1. Modify FeePaymentController

**File:** `app/Http/Controllers/Fee/FeePaymentController.php`

**Current:** After saving payment to `fee_payments` table

**Change to:** Use FinanceService
```php
// In store() method, after saving payment:

// OLD CODE (remove):
// $payment = FeePayment::create([...]);

// NEW CODE (add):
app(\App\Services\FinanceService::class)->createIncomeTransaction([
    'amount' => $validated['received_amount'],
    'payment_method' => $validated['payment_method'],
    'category_id' => $this->getCategoryIdForFee($feeHeadId), // Map fee head to category
    'student_id' => $student->id,
    'reference_type' => 'App\\Models\\Fee\\FeeVoucher',
    'reference_id' => $voucher->id,
    'campus_id' => $validated['campus_id'],
    'description' => 'Fee payment for ' . $voucher->voucher_no,
]);
```

### 2. Modify PurchasesController

**File:** `app/Http/Controllers/Inventory/PurchasesController.php`

**Current:** After saving payment to `purchase_payments` table

**Change to:** Use FinanceService
```php
// In storePayment() method, after saving payment:

// OLD CODE (remove):
// $payment = PurchasePayment::create([...]);

// NEW CODE (add):
app(\App\Services\FinanceService::class)->createExpenseTransaction([
    'amount' => $validated['amount'],
    'payment_method' => $validated['payment_mode'],
    'category_id' => $this->getCategoryIdForSupplier($supplierId), // Map supplier to category
    'supplier_id' => $supplier->id,
    'reference_type' => 'App\\Models\\Purchase',
    'reference_id' => $purchase->id,
    'campus_id' => $validated['campus_id'],
    'description' => 'Payment for ' . $purchase->purchase_id,
]);
```

---

## Future Extensibility

### Adding Transport Module

**Step 1:** Create Transport module normally
- Transport routes, controllers, models
- Student transport subscriptions

**Step 2:** When collecting transport fee:
```php
app(\App\Services\FinanceService::class)->createIncomeTransaction([
    'amount' => $request->amount,
    'payment_method' => $request->payment_method,
    'category_id' => 3, // Transport Fee (pre-created)
    'student_id' => $student->id,
    'reference_type' => 'App\\Models\\Transport\\TransportFee',
    'reference_id' => $transportFee->id,
    'campus_id' => $request->campus_id,
    'description' => 'Transport fee - ' . $route->name,
]);
```

**Result:** Automatically appears in Finance Dashboard!

### Adding Library Module

**Step 1:** Create Library module
- Book issues, returns, fines

**Step 2:** When collecting library fine:
```php
app(\App\Services\FinanceService::class)->createIncomeTransaction([
    'amount' => $fineAmount,
    'payment_method' => 'cash',
    'category_id' => 8, // Late Fine
    'student_id' => $student->id,
    'reference_type' => 'App\\Models\\Library\\LibraryFine',
    'reference_id' => $fine->id,
    'campus_id' => $campusId,
    'description' => 'Library fine - ' . $fine->reason,
]);
```

---

## Summary

### What to DELETE (Tables):
| Table | Model | Action |
|-------|-------|--------|
| `fee_payments` | `App\Models\Fee\FeePayment` | DELETE from code, keep migration history |
| `purchase_payments` | `App\Models\PurchasePayment` | DELETE from code, keep migration history |

### What to ADD (New):

**Tables:**
- `ledgers` - Main financial transactions
- `ledger_categories` - Income/Expense categories
- `payment_methods` - Payment methods

**Models:**
- `App\Models\Ledger\Ledger.php`
- `App\Models\Ledger\LedgerCategory.php`
- `App\Models\Ledger\PaymentMethod.php`

**Controllers:**
- `App\Http\Controllers\Finance\FinanceController.php`
- `App\Http\Controllers\Finance\TransactionController.php`
- `App\Http\Controllers\Finance\ReceivePaymentController.php`
- `App\Http\Controllers\Finance\MakePaymentController.php`
- `App\Http\Controllers\Finance\CategoryController.php`
- `App\Http\Controllers\Finance\PaymentMethodController.php`
- `App\Http\Controllers\Finance\ReportController.php`

**Services:**
- `App\Services\FinanceService.php`

**Routes:**
- `routes/finance.php`

**Vue Pages:**
- `resources/js/pages/Finance/Dashboard.vue`
- `resources/js/pages/Finance/Transactions.vue`
- `resources/js/pages/Finance/ReceivePayment.vue`
- `resources/js/pages/Finance/MakePayment.vue`
- `resources/js/pages/Finance/Categories.vue`
- `resources/js/pages/Finance/PaymentMethods.vue`

### What to KEEP (Business Tables):
| Table | Model | Purpose |
|-------|-------|---------|
| `fee_vouchers` | `App\Models\Fee\FeeVoucher` | Student fee records |
| `fee_voucher_items` | `App\Models\Fee\FeeVoucherItem` | Fee line items |
| `fee_voucher_adjustments` | `App\Models\Fee\FeeVoucherAdjustment` | Discounts/fines |
| `inventory_purchases` | `App\Models\Purchase` | Supplier purchase records |
| `inventory_purchase_items` | `App\Models\PurchaseItem` | Purchase line items |

### How It Works:
1. Student pays fee → Creates ledger entry (INCOME)
2. Pay supplier → Creates ledger entry (EXPENSE)
3. Dashboard shows ALL money in one place
4. Reports show complete financial picture

### Benefits:
- Single source of truth for money
- Easy financial reports
- Add any module without changing finance
- Complete audit trail
- Works for any size school

---

## Next Steps

1. ✅ Create this roadmap document
2. Create the three new tables (ledgers, ledger_categories, payment_methods)
3. Create the Finance menu in MenuSeeder
4. Create FinanceService
5. Create all Finance controllers
6. Create Vue.js pages
7. Modify FeePaymentController to use FinanceService
8. Modify PurchasesController to use FinanceService
9. Test end-to-end flow

---

*Document created: March 2026*
*For: Zamora School Management System*
