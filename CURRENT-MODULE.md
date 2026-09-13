# Where the project stands

A plain statement of what is built, as of **13 September 2026**. Rough or
polished, if it works it is listed here.

The finished module records — every fault found and what was done about it —
are in [docs/MODULE-LOG.md](docs/MODULE-LOG.md). The rules the work follows are
in [docs/WORKING-RULES.md](docs/WORKING-RULES.md).

**905 tests pass. PHPStan is at zero. Psalm is at its baseline of 10. Pint is
clean.**

---

## Modules that have been through a full review

Four, in this order. Each one was read end to end — schema, models, services,
controllers, requests, routes — then fixed and covered with tests.

| Module | Tests | What it does |
|---|---|---|
| **Fee** | 153 | Fee structures, per-item frequency, discounts and concessions, sibling discounts, fine slabs, proration, annual instalments, one-time-fee refunds, voucher generation, the bank challan |
| **Attendance** | 246 | Daily registers, lock and auto-lock, holidays, leave applications, late arrivals and late fines, biometric punch import, consecutive-absence alerts, working-day calculation, monthly summaries, class and student reports, teacher class assignments |
| **Exam** | 201 | Exams and papers, registration from the roll, marking, grading scales, grace marks, subject roles, pass and fail, position in class, the result card, the date sheet, the annual result, rechecking, publishing and locking |
| **Student** | 278 | Admission with guardians and fee mode, the enrolment lifecycle (leaving, re-admission, transfer, promotion), the leaving certificate, ID cards, sibling links, CSV export and import, admission enquiries |
| **Staff** | 89 | One person/many jobs, campus and salary authorisation, the personal file, teaching assignments, staff attendance and leave, named salary components and payroll, the dashboard and every screen |

---

## What a school can actually do today

**Admit a child.** The full admission form: identity, B-Form, guardians
(deduplicated by phone), class and section, and the fee — from a structure, with
a discount, or typed in by hand. A login is created for the child and the
guardian, and the passwords are shown once so the family can be given them.

**Bill them.** Vouchers generated per month from the structure, carrying
arrears, fines by slab, discounts, sibling concessions and proration for a child
who joined mid-month. Printed as a bank challan.

**Take the register.** Daily, per section, with holidays and leave respected.
Late arrivals fined by the school's own rule. Biometric punches imported where a
machine is fitted. A register signs off and locks.

**Run an exam.** Timetable the papers, register the class from the roll as it
stood on the day, enter marks, grade against a scale that knows the campus and
the session. Give grace marks. Say who passed. Work out positions. Print the
result cards with the attendance line on them, and the date sheet for the
notice board.

**Close the year.** Weight the terms into an annual result, then promote a whole
section — promoted, detained, or promoted on condition — and undo it if it was
wrong.

**Let a child go.** Record when they left and why, and print the School Leaving
Certificate.

**Who sees what.** Three widths, decided in one place and shared by every
module: school-wide, campus, or only the classes a teacher has been given. Every
list is filtered to match the record checks.

---

## Modules not yet reviewed

Built, in use, and never read end to end:

| | |
|---|---|
| **Inventory** | 10 controllers, the largest unreviewed area |
| **Transport** | Vehicles, routes, stops, student assignments |

Staff was reviewed and rebuilt across eight phases rather than by the
read-end-to-end process every other row in this file goes through — its full
record is in [docs/MODULE-LOG.md](docs/MODULE-LOG.md). Settings (16
controllers) was reviewed and fixed the same way Fee/Finance was — full
findings in [docs/MODULE-LOG.md](docs/MODULE-LOG.md) under "Module: Settings".

---

## How a module gets done

1. I read it end to end and write the findings into this file, numbered.
2. You say which to fix — or say "all of them".
3. I fix, test, and delete each entry as it is done.
4. When the list is empty the record moves to
   [docs/MODULE-LOG.md](docs/MODULE-LOG.md) and this file is refilled with the
   next module.

---

# Current module: Fee (second half) + Finance

Taken together because the second half of Fee **is** the payment side, and
every payment already posts — or should post — into Finance's ledgers. Reading
them apart would mean reading the same journal-posting code twice.

**What was read.** `FeePaymentController`, `FeeVoucherController`,
`FeeReportController`, `FinanceController` and its six sibling controllers
(`TransactionController`, `ReceivePaymentController`, `MakePaymentController`,
`CategoryController`, `PaymentMethodController`, `ReportController`,
`StudentAccountStatementController`), `FeeService`s (`StudentBillingService`,
`UnifiedAccountingService`, `FinanceService`), `FeePayment`, `FeeVoucher`,
`Ledger`, `routes/fee.php`, `routes/finance.php`, and the migrations behind
`new_fee_payments`, `ledgers`, `chart_of_accounts`. Cross-checked against
`tests/Feature/Fee/` and `tests/Feature/Finance/` — the second is empty; the
first has zero coverage for payments, vouchers-as-a-screen, or reports.

## Findings — all fixed except FF7

| | |
|---|---|
| **FF1 — no authorisation anywhere, again — fixed.** | Every route in `routes/fee.php` and `routes/finance.php` sat on `web, auth` alone. Fixed with `permission:` middleware on every route (the ten seeded `fee.*` and six `finance.*` permissions, now actually checked), new `FeePaymentPolicy`/`FeeVoucherPolicy`/`FeeHeadPolicy` on the shared `ChecksSchoolReach` trait for campus-width record checks, and the `Gate::authorize(...)` calls in `FeeHeadController` — previously written and commented out — uncommented and wired to the new `FeeHeadPolicy`. `FeeVoucher`/`FeePayment` both gained a `scopeVisibleTo()`, matching every other module. |
| **FF2 — recording a payment always crashed — fixed.** | `$validated['vouchers']` → `$validated['charges']`; the dead no-op loop removed. |
| **FF3 — the Collection Report always crashed — fixed.** | `wallet_amount` → `excess_amount` in the summary sum. |
| **FF4 — the Finance dashboard read the wrong campus — fixed.** | `auth()->user()->campus_id` → `auth()->user()?->campusId()`. |
| **FF5 — `whereBetween` against date-cast columns — fixed.** | Two `whereDate()` calls, everywhere this pattern was found: `FinanceService::getTotalIncome()`/`getTotalExpense()`, `Ledger::scopeDateRange()`, `FinanceController::index()`'s `TransportVehicleExpense` query. |
| **FF6 — `DATEDIFF()`, MySQL-only — fixed.** | `FeeReportController::defaulters()` now computes the cutoff date in PHP (`now()->subDays($n)`) and compares with `whereDate()` — portable, and provably right on SQLite. |
| **FF7 — two accounting systems, kept in sync by a `try`/`catch` that only logs — NOT fixed, needs a decision.** | Still true: every payment writes to both the legacy `Ledger` and the double-entry `JournalEntry` system, the legacy write can silently fail, and the Finance dashboard reads only the legacy one. Resolving this means picking one of: point the dashboard at `JournalEntry` instead, make the legacy write failure surface somewhere a person sees it, or stop double-writing entirely — each is a real design call, not a bug fix, so this was deliberately left alone rather than guessed at. |
| **FF8 — a hardcoded category id — fixed.** | `createFinanceLedgerEntry()` now resolves (or creates, the first time) a `LedgerCategory` named "Tuition Fee" rather than assuming id `1` — there was no seeder for `ledger_categories` at all, so the hardcoded id was never guaranteed to mean anything. |
| **FF9 — vouchers publicly printable by guessing an id — fixed.** | `print-voucher.*` now requires Laravel's `signed` middleware instead of being a bare public route; `FeeVoucherController::authorizePrint()` allows an authenticated, permitted user through the normal way, or an anonymous request only if its signature validates. |
| **FF10 — eleven routes called controller methods that did not exist — fixed.** | All eleven implemented: `FeeVoucherController::create` (alias of `generateForm`), `destroy`, `generateBulk` (the same run as `generate()`, across every active class in a campus), `publish`, `addAdjustment` (writes a `FeeVoucherAdjustment` and adjusts the voucher balance by type), `logPrint` (a `FeeVoucherPrintLog` row); `FeePaymentController::destroy`, `printReceipt` (aliases `receipt`), `reverse` (undoes the allocations, reverses the wallet advance with an offsetting debit, voids both accounting entries, marks the payment `reversed`), `getByStudent`, `getByVoucher`. |
| **FF11 — found while fixing FF2: a `TypeError` `createFinanceLedgerEntry()`'s `catch` could never see — fixed.** | `$paymentMethodMap[$payment->payment_method]` used an enum instance as an array key — a `TypeError`, uncaught, since `TypeError extends Error` and the `catch` block only caught `\Exception`. Every payment that reached this line crashed the whole request rather than merely failing to log. Fixed to read `->value` first, and the `catch` widened to `\Throwable`. |
| **FF12 — found while re-checking FF1: `by-student` never read the id the route bound — fixed.** | `routes/fee.php` names `/vouchers/student/{student}` and `/payments/student/{student}`, but both controller methods only ever read `$request->student_id` off the query string and ignored the bound `{student}` entirely. Neither endpoint has ever worked from the URL shape its own route declares — a caller had to redundantly repeat the id as `?student_id=`. Fixed to type-hint `Student $student` and read the bound model. |
| **FF13 — found while re-checking FF1: `fee.view.own` was never wired, so closing the hole would have locked every family out of their own records — fixed.** | The `student`/`guardian` roles hold `fee.view.own` and nothing else fee-related — the intended way a family reads their own vouchers and receipts, the same shape `exam.result.view.own` already has in `ExamResultHeaderPolicy`. FF1's new policies checked only the general `fee.view`/`fee.voucher.view`/`fee.payment.collect` abilities, so a portal account could no longer see even its own child's fee record. Fixed with the same `isTheirOwn`/`$user->student` pattern as the exam policy: `FeePaymentPolicy`/`FeeVoucherPolicy` gained `view()` own-record branches, `viewByStudent()` for the `by-student` list endpoints, and `print()` (vouchers) gained the same check; the relevant routes gained `\|fee.view.own` alongside the staff permissions. |
| Also fixed along the way | `FeeVoucherController::getOverdue()`'s bare `orWhere` let every campus's overdue vouchers through a `campus_id` filter (`AND` binds tighter than `OR` in the SQL it built) — grouped correctly. `edit()`'s adjustments mapping read `$adjustment->type`, a column that does not exist (`adjustment_type` does) — always rendered null. |

## Suggestions

| | |
|---|---|
| **FS1 — done.** | `FeePaymentController`/`FeeVoucherController` no longer validate inline: eleven new Form Request classes under `app/Http/Requests/Fee/` (`StoreFeePaymentRequest`, `ReverseFeePaymentRequest`, `GetApplicableFeeStructureRequest`, `GenerateVouchersRequest`, `GenerateVouchersBulkRequest`, `AddVoucherAdjustmentRequest`, `UpdateFeeVoucherRequest`, `AddVoucherItemRequest`, `UpdateVoucherItemRequest`), matching the `authorize(): true` + Gate-in-controller convention `StoreFeeHeadRequest` already used. `FeeReportController` was left alone — its inputs are optional report filters with nothing to reject, not a resource to validate. |
| **FS2 — largely addressed.** | New coverage: `tests/Feature/Fee/{Access,Payments,Reports,Vouchers}` and `tests/Feature/Finance/{Access,Dashboard}` — authorisation (including the own-record case, FF13), the payment record/reverse flow, both crashing reports, the eleven new voucher/payment actions, the signed print link, and the by-student endpoints (FF12). 193 Fee+Finance tests pass (was 153, all in Fee, none in Finance). |

**Not yet read in depth:** `FeeStructureController`, `FeeStructureItemController`, `DiscountTypeController`, `FineRuleController`, `FeeSettingsController`, `FeeDashboardController` — FF1's `permission:` middleware now covers their routes, but their business logic has not been read end to end the way payments/vouchers/reports/Finance now have been. None had any `Gate::authorize` call before this pass either, so the middleware is the first authorisation they have ever had.

**Known limitation, not fixed:** `FeePaymentController::reverse()` restores a payment's wallet advance with an offsetting debit, but does not check whether that advance has since been spent elsewhere (via `advance_adjusted_amount` on a later voucher) — reversing in that case could leave the wallet ledger negative. Left alone deliberately: blocking a reversal in that state is a business-rule decision (refuse the reversal? net the two? ask which voucher to unwind first?), not a bug fix, in the same spirit as FF7.

**What to do now:** FF7 is the one open item — a design decision about the two accounting systems, not a bug fix. Otherwise this module's known findings are closed; say if the not-yet-read-in-depth controllers above should be the next thing looked at, or move on to Inventory/Transport/Settings.
