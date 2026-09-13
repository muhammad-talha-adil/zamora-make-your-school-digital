# Backend-Without-Screen Audit

Scope: Fee, Finance, Attendance, Exam, Student, Staff, Inventory modules.
Checks: (1) `Inertia::render()`/`inertia()` targets that have no matching `.vue` file, and (2) controller
actions with real logic that no frontend page/route call reaches.

## Fee

- `FeePaymentController::reverse()` — route `fee.payments.reverse` (`POST /fee/payments/{payment}/reverse`).
  No frontend page/action references this route. The controller has a full transactional reversal
  workflow (undoes allocations, restores charges, requires a reason via `ReverseFeePaymentRequest`), but
  `Fee/Payments/Index.vue` only shows a "reversed" status badge — there is no button/flow that actually
  calls reverse.

## Finance

No gaps found.

## Attendance

- `StudentLeaveController` (`app/Http/Controllers/StudentLeaveController.php`) — routes `student-leaves.pending`,
  `student-leaves.index`, `student-leaves.store`, `student-leaves.approve`, `student-leaves.reject`.
  No Vue page and no frontend code anywhere references any `student-leaves.*` route. This is a complete
  student-leave-application feature (apply, list pending, approve, reject) with no screen wired to reach it
  at all — not even a partial one.

## Exam

- `ExamReportCardController::recomputePositions()` — route `exam.positions.recompute`
  (`POST /exam/{examId}/positions`). No frontend reference. Real logic (recomputes exam rank/position via
  `PositionCalculator`), meant to be a "recompute positions" action button, but nothing calls it.
- `ExamRevaluationController::review()`, `::approve()`, `::reject()`, `::applyChange()` — routes
  `exam.revaluations.review`, `.approve`, `.reject`, `.apply-change`. `Exam/Revaluations/Index.vue` only
  calls the `request`-creation endpoint (`/exam/revaluations/request`); the office-side workflow to review,
  approve, reject, or apply an approved mark change is fully implemented server-side but has no UI at all.
- `StudentPromotionController::page()` — no matching Vue file at `Students/Promotion/Index` (there is no
  `Promotion` folder anywhere under `resources/js/pages/students` or `Students`, only `Create/Edit/Index/Show`).
  This also explains why `students.promotion.run`/`.preview`/`.revert` show no frontend usage — the page that
  would call them doesn't exist.
- `ExamSettingsController::editGradeScale()` (grade scale edit form) — no matching Vue file at
  `Exam/Settings/GradeScales/Edit` (only `Index.vue` and `Create.vue` exist in that folder).

## Student

- Covered above under Exam: `Students/Promotion/Index` render target is missing entirely.

## Staff

- `StaffProfileController::leave()` and `::rejoin()` — routes `staff.people.leave`, `staff.people.rejoin`.
  No frontend reference anywhere. Both implement real employment-status changes (mark a staff member as
  having left / taken back on, via `StaffEmploymentService`), but `Staff/People/Show.vue` has no button
  wired to either action.

## Inventory

- `InventoryReturnsController::show()` — no matching Vue file at `inventory/ReturnShow` (actual pattern used
  elsewhere is `Folder/Show.vue`; no such file exists under `inventory/Returns/`, only `Returns/Index.vue`).
- `PurchasesController::show()` — no matching Vue file at `inventory/Purchases/Show` (only
  `inventory/Purchases/Index.vue` exists; a similarly-named `inventory/PurchaseView.vue` exists but is a
  different route/controller and isn't what this render call points at).
- `InventoryStocksController::reserve()` / `::release()` — routes `inventory.stocks.reserve` /
  `.release`. No frontend reference; `inventory/Stocks/Index.vue` only displays `reserved_quantity`, it
  never calls either action.
