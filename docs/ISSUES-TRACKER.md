# Issues Tracker — Zamora

> **Legend:** P0 = security/access-control bug (fix first), P1 = broken functionality (errors / silent failures), P2 = project-wide UI/UX consistency pattern (one fix, many places), P3 = real feature gap / bigger redesign, P4 = needs an explicit owner decision before any code changes. `[#N]` = owner's original issue number, linked to `docs/ISSUES-RAW.md#issue-n` where the exact original wording lives. This file is planning/organizing only — no code was changed to produce it.

---

## PART 1 — Data Table / List Inventory (UI)

Every page under `resources/js/pages/` that renders a data table or paginated list, grouped by module. Use this as the checklist for any project-wide table change (e.g. P2 items: `#` column, pagination-only-when-needed, status toggle, icon-only actions).

### Students / Admissions / Enquiries

| Vue file | Route (name / URL) | Backing model / table |
|---|---|---|
| `resources/js/pages/students/Index.vue` | `students.index` → `/students` | `Student` (`students`) |
| `resources/js/pages/students/Enquiries/Index.vue` | `students.enquiries` (page) + `students.enquiries.list` → `/students/enquiries` | `AdmissionEnquiry` (`admission_enquiries`) |
| `resources/js/pages/students/Promotion/Index.vue` | `students.promotion` → `/students/promotion` | `Student` / `StudentEnrollmentRecord` (`student_enrollment_records`) |
| `resources/js/pages/students/Show.vue` | `students.show` → `/students/{student}` | `Student`, related tabs (guardians, fees, attendance) |

### Fee Module

| Vue file | Route | Model / table |
|---|---|---|
| `resources/js/pages/Fee/FeeHeads/Index.vue` | `fee.heads.index` → `/fee/heads` | `FeeHead` (`fee_heads`) |
| `resources/js/pages/Fee/DiscountTypes/Index.vue` | `fee.discount-types.index` → `/fee/discount-types` | `DiscountType` (`discount_types`) |
| `resources/js/pages/Fee/Structures/Index.vue` | `fee.structures.index` → `/fee/structures` | `FeeStructure` (`fee_structures`) |
| `resources/js/pages/Fee/Vouchers/Index.vue` | `fee.vouchers.index` → `/fee/vouchers` | `NewFeeVoucher` (`new_fee_vouchers`) |
| `resources/js/pages/Fee/Payments/Index.vue` | `fee.payments.index` → `/fee/payments` | `NewFeePayment` (`new_fee_payments`) |
| `resources/js/pages/Fee/Settings/Index.vue` (tabs) | `fee.settings.index` → `/fee/settings` | tabs re-embed FeeHeads/DiscountTypes/FineRules lists |
| `resources/js/pages/Fee/Settings/FineRules.vue` | `fee.settings.fine-rules` → `/fee/settings/fine-rules` | `FeeFineRule` (`fee_fine_rules`) |
| `resources/js/pages/Fee/Reports/Index.vue` | `fee.reports.index` → `/fee/reports` | aggregate report tables |
| `resources/js/pages/Fee/Reports/Collection.vue` | `fee.reports.collection` | `NewFeePayment` aggregates |
| `resources/js/pages/Fee/Reports/Outstanding.vue` | `fee.reports.outstanding` | `NewFeeVoucher` aggregates |
| `resources/js/pages/Fee/Reports/Defaulters.vue` | `fee.reports.defaulters` | `NewFeeVoucher` aggregates |
| `resources/js/pages/Fee/Reports/PaymentMethods.vue` | `fee.reports.payment-methods` | `NewFeePayment` / `PaymentMethod` |
| `resources/js/pages/Fee/Vouchers/Show.vue` | `fee.vouchers.show` → `/fee/vouchers/{voucher}` | `NewFeeVoucher`, `FeeVoucherItem` |
| `resources/js/pages/Fee/Payments/Show.vue` | `fee.payments.show` → `/fee/payments/{payment}` | `NewFeePayment`, `FeePaymentAllocation` |

### Exams

| Vue file | Route | Model / table |
|---|---|---|
| `resources/js/pages/Exam/Exams/Index.vue` | `exam.index-page` → `/exams` | `Exam` (`exams`) |
| `resources/js/pages/Exam/Papers/Index.vue` | `exam.papers.index-page` → `/exams/papers` | `ExamPaper` (`exam_papers`) |
| `resources/js/pages/Exam/Registrations/Index.vue` | `exam.registrations.index-page` → `/exams/registrations` | `ExamStudentRegistration` (`exam_student_registrations`) |
| `resources/js/pages/Exam/Results/Index.vue` | `exam.results.index-page` → `/exams/results` | `ExamResultHeader` / `ExamResultLine` |
| `resources/js/pages/Exam/Results/Annual.vue` | `exam.results.annual-page` | aggregated results |
| `resources/js/pages/Exam/Revaluations/Index.vue` | `exam.revaluations.index-page` → `/exams/revaluations` | `ExamRevaluationRequest` (`exam_revaluation_requests`) |
| `resources/js/pages/Exam/Settings/Index.vue` | `exam.settings.index-page` → `/exams/settings` | `ExamType` (`exam_types`), `GradeSystem` |
| `resources/js/pages/Exam/Settings/GradeScales/Index.vue` | `exam.settings.grade-scales-page` | `GradeSystem` (`grade_systems`) |
| `resources/js/pages/Exam/Dashboard/Index.vue` | `exam.dashboard.index-page` → `/exams/dashboard` | aggregate widgets |
| `resources/js/pages/Exam/Marking/Grid.vue` / `MarkingGridComponent.vue` | `exam.marking.grid-page` → `/exams/marking/grid` | `ExamResultLine` grid |
| `resources/js/pages/Exam/Marking/Grace.vue` | `exam.marking.grace-page` → `/exams/marking/grace` | `ExamResultLine` (grace marks) |

### Attendance

| Vue file | Route | Model / table |
|---|---|---|
| `resources/js/pages/attendance/Index.vue` | `attendance.index` → `/attendance` | `Attendance` / `AttendanceStudent` (`attendances`, `attendance_students`) |
| `resources/js/pages/attendance/Dashboard.vue` | `attendance.dashboard` → `/attendance/dashboard` | aggregate widgets |
| `resources/js/pages/attendance/ClassReport.vue` | `attendance.class-report` → `/attendance/class/report` | `AttendanceSummary` (`attendance_summaries`) |
| `resources/js/pages/attendance/StudentReport.vue` | `attendance.student-report` → `/attendance/student/{student}/report` | `AttendanceStudent`, `AttendanceSummary` |
| `resources/js/pages/attendance/StudentLeaves/Index.vue` | `attendance.leaves.index` / `.page` → `/attendance/leaves` | `StudentLeave` (`student_leaves`) |
| `resources/js/pages/attendance/Settings.vue` | `attendance.settings` → `/attendance/settings` (+ leave-types, holidays sub-lists) | `LeaveType` (`leave_types`), `Holiday` (`holidays`), `AttendancePolicy` |

### Staff

| Vue file | Route | Model / table |
|---|---|---|
| `resources/js/pages/Staff/People/Index.vue` | `staff.people.index` → `/staff/people` | `StaffProfile` (staff module tables from `build_staff_module_foundation`) |
| `resources/js/pages/Staff/People/Show.vue` | `staff.people.show` → `/staff/people/{staffProfile}` | `StaffProfile` + related tabs |
| `resources/js/pages/Staff/Teaching/Index.vue` | `staff.teaching.index` / `.page` → `/staff/teaching` | `TeacherClassAssignment` (`teacher_class_assignments`) |
| `resources/js/pages/Staff/Attendance/Index.vue` | `staff.attendance.page` → `/staff/attendance` | staff attendance tables |
| `resources/js/pages/Staff/Payroll/Index.vue` | `staff.payroll.page` → `/staff/payroll` | payroll tables (staff module) |
| `resources/js/pages/Staff/Settings/Index.vue` | `staff.settings.page` → `/staff/settings` | `StaffDesignation` (`staff_designations`), `StaffDocumentType` |
| `resources/js/pages/Staff/Dashboard.vue` | staff dashboard | aggregate widgets |

### Settings / Sessions / Classes / Sections / Subjects

| Vue file | Route | Model / table |
|---|---|---|
| `resources/js/pages/settings/SchoolProfile.vue` (tabs: campuses, sessions, classes, sections, subjects, class-subjects) | `settings.school-profile.show` → `/settings/school-profile` | multiple, see below |
| — Campuses tab | `settings.campuses.index` → `/settings/campuses` | `Campus` (`campuses`) |
| — Campus Types (in Add Campus modal) | `settings.campus-types.getAll` | `CampusType` (`campus_types`) |
| — Sessions tab | `settings.sessions.index` → `/settings/sessions` | `Session` (`sessions`, schools' academic session table — not Laravel's own `sessions`) |
| — Classes tab | `settings.school-classes.index` → `/settings/school-classes` | `SchoolClass` (`school_classes`) |
| — Sections tab | `settings.sections.index` → `/settings/sections` | `Section` (`sections`) |
| — Subjects tab | `settings.subjects.index` → `/settings/subjects` | `Subject` (`subjects`) |
| — Assign Subjects to Class Sections tab | `settings.class-subjects.index` → `/settings/class-subjects` | `ClassSubject` (`class_subject`) |
| — Exam Types tab | `settings.exam-types.index` → `/settings/exam-types` | `ExamType` (`exam_types`) |
| — Months (lookup, no-CRUD) | `settings.months.index` → `/settings/months` | `Month` (`months`) |
| `resources/js/pages/settings/Menus/Index.vue` | `settings.menus.index` → `/settings/menu-settings` | `Menu` (`menus`) |
| `resources/js/pages/settings/ActivityLog.vue` | `settings.activity-log.index` → `/settings/activity-log` | Spatie `activity_log` |

### Inventory

| Vue file | Route | Model / table |
|---|---|---|
| `resources/js/pages/inventory/Items/Index.vue` | `inventory.items.index` → `/inventory/items` | `InventoryItem` (`inventory_items`) |
| `resources/js/pages/inventory/Types/Index.vue` | `inventory.types.index` → `/inventory/types` | `InventoryType` (`inventory_types`) |
| `resources/js/pages/inventory/Stocks/Index.vue` | `inventory.stocks.index` → `/inventory/stocks` | `InventoryStock` (`inventory_stocks`) |
| `resources/js/pages/inventory/ItemsStock.vue` | `/inventory/items-stock` | joined items+stocks |
| `resources/js/pages/inventory/Suppliers/Index.vue` | `inventory.suppliers.index` → `/inventory/suppliers` | `Supplier` (`suppliers`) |
| `resources/js/pages/inventory/Adjustments/Index.vue` | `inventory.adjustments.index` → `/inventory/adjustments` | `InventoryAdjustment` (`inventory_adjustments`) |
| `resources/js/pages/inventory/Purchases/Index.vue` | `inventory.purchases.index` → `/inventory/purchases` | `InventoryPurchase` (`inventory_purchases`) |
| `resources/js/pages/inventory/PurchasesManage.vue` | `/inventory/purchases-manage` | `InventoryPurchase` + items |
| `resources/js/pages/inventory/PurchaseReturns/Index.vue` | `inventory.purchase-returns.index` → `/inventory/purchase-returns` | `PurchaseReturn` (`purchase_returns`) |
| `resources/js/pages/inventory/Returns/Index.vue` | `inventory.returns.index` → `/inventory/returns` | `InventoryReturn` (`inventory_returns`) |
| `resources/js/pages/inventory/StudentInventories/Index.vue` | `inventory.student-inventories.index` → `/inventory/student-inventories` | `StudentInventoryRecord` (`student_inventory_records`) |
| `resources/js/pages/inventory/StudentManage.vue` | `/inventory/student-manage` | student inventory joined view |
| `resources/js/pages/inventory/Index.vue` | `inventory.index` → `/inventory` | dashboard aggregate |

### Finance / Transport

| Vue file | Route | Model / table |
|---|---|---|
| `resources/js/pages/Finance/Transactions.vue` | `finance.transactions.index` → `/finance/transactions` | `Ledger`/transaction tables (`ledgers`) |
| `resources/js/pages/Finance/Categories.vue` | `finance.categories.index` → `/finance/categories` | `LedgerCategory` (`ledger_categories`) |
| `resources/js/pages/Finance/PaymentMethods.vue` | `finance.payment-methods.index` → `/finance/payment-methods` | `PaymentMethod` (`payment_methods`) |
| `resources/js/pages/Finance/Reports/CashBook.vue` | `finance.reports.cash-book` | ledger aggregates |
| `resources/js/pages/Finance/Reports/Income.vue` | `finance.reports.income` | ledger aggregates |
| `resources/js/pages/Finance/Reports/Expense.vue` | `finance.reports.expense` | ledger aggregates |
| `resources/js/pages/Finance/StudentAccountStatement.vue` | `finance.student-account-statement.index` | `StudentFeeWalletTransaction` etc. |
| `resources/js/pages/Transport/Index.vue` | `transport.index` → `/transport` | transport module tables (`create_staff_and_transport_module_tables`) |

**Total tables/lists inventoried: 52** (across Students/Enquiries: 4, Fee: 13, Exams: 11, Attendance: 6, Staff: 7, Settings/Sessions/Classes/Sections/Subjects: 11, Inventory: 12 — note some pages listed twice across sub-tabs are counted once per distinct Vue file; see exact per-module counts in each table above).

---

## PART 2 — Categorized Issue List

### P0 — Security / Access-Control Bugs

**Status: ✅ ALL FIXED** (commit `2fcfd0f`)

| Issue(s) | Summary |
|---|---|
| ✅ [#2](ISSUES-RAW.md#issue-2) | Jab "School is Active" off ho, sirf developer + owner hi login kar sakein — baaki sab ko block karo. |
| ✅ [#15](ISSUES-RAW.md#issue-15) | `/settings/menu-settings` sirf developer ko show ho. |
| ✅ [#16](ISSUES-RAW.md#issue-16) | `/settings/activity-log` sirf owner + developer ko show ho. |
| ✅ [#3](ISSUES-RAW.md#issue-3) | Public website off hone par login navbar me pages ke buttons bhi hide hon. |
| ✅ [#52, #53](ISSUES-RAW.md#issue-52) | Sensitive actions (Activate/Deactivate/Delete/status-change) par har baar confirmation aaye + owner/admin password server-side verify ho, sirf JS se nahi. |
| ✅ [#112](ISSUES-RAW.md#issue-112) | Owner ko khud `/fee/print-voucher/1` access nahi mil raha — permission/policy bug. |

### P1 — Broken Functionality (errors / silent failures)

**Status: ✅ ALL FIXED** (commits `2b419a1`, `9427f25`, `2e67a22`, `08ecb7c`)

| Issue(s) | Summary |
|---|---|
| ✅ [#56](ISSUES-RAW.md#issue-56) | Class add karne ke baad Classes table kabhi kabhi refresh nahi hoti / show hi nahi hoti. |
| ✅ [#57](ISSUES-RAW.md#issue-57) | Naye classes Section dropdown me available nahi hote. |
| ✅ [#58](ISSUES-RAW.md#issue-58) | Sections toggle karne par data ghayab ho jata hai, action buttons responsive nahi. |
| ✅ [#60](ISSUES-RAW.md#issue-60) | Session Activate/Deactivate aur Delete action error dete hain, kaam nahi karte. |
| ✅ [#62](ISSUES-RAW.md#issue-62) | Subjects toggle karne par records ghayab ho jate hain. |
| ✅ [#24](ISSUES-RAW.md#issue-24) | Class add karne ke baad Sections tab ke Class dropdown me naya class dikhne ke liye page refresh chahiye hota hai. |
| ✅ [#67](ISSUES-RAW.md#issue-67) | Admission form me fee structure manual entry input bohot slow type hota hai. |
| ✅ [#70](ISSUES-RAW.md#issue-70) | Manual entry sirf 1 fee head ke liye select karne par system sab heads me manual entry maangta hai — validation bug. |
| ✅ [#77, #78](ISSUES-RAW.md#issue-77) | Add Exam Type ke baad modal auto close nahi hota, table refresh nahi hoti — manual page refresh karna padta hai. |
| ✅ [#87](ISSUES-RAW.md#issue-87) | "Existing papers found for this combination" jab dikh raha hota hai jab wo actually informational message hai, error jaisa treat ho raha hai. |
| ✅ [#88](ISSUES-RAW.md#issue-88) | Exams status dropdown har case me sahi se functional hai ya nahi — confirm/fix. |
| ✅ [#90](ISSUES-RAW.md#issue-90) | Max-marks exceed karne par validation error UI tod deta hai (input field upar chala jata hai), marking grid table responsive nahi. |
| ✅ [#92](ISSUES-RAW.md#issue-92) | Exam status save karne ke baad manual page refresh chahiye hota hai. |
| ✅ [#93](ISSUES-RAW.md#issue-93) | Koi filter select kiye baghair bhi "Top 5 Toppers" show ho raha hai — galat baseline se calculate ho raha hai. |
| ✅ [#95](ISSUES-RAW.md#issue-95) | Result me kisi student ka Fail-status calculation samajh nahi aa raha — logic verify karo. |
| ✅ [#102](ISSUES-RAW.md#issue-102) | Attendance create page par status dropdown selected text show nahi karta, placeholder hi rehta hai. |
| ✅ [#111](ISSUES-RAW.md#issue-111) | "Add Custom Fee Head" modal ka dropdown sahi render nahi ho raha. |
| ✅ [#121](ISSUES-RAW.md#issue-121) | Overpayment (voucher se zyada amount) automatically credit me chala jata hai — abhi ke liye ye disallow hi karna hai (voucher amount se zyada submit hi na ho sake). |
| ✅ [#72, #74](ISSUES-RAW.md#issue-72) | Student edit page aur ID cards page par images load nahi ho rahi. |
| ✅ [#73](ISSUES-RAW.md#issue-73) | Sidebar me Student menu ke andar Promotion button kaam nahi kar raha. |
| ✅ [#34](ISSUES-RAW.md#issue-34) | Leaving Certificate feature bilkul kaam nahi kar raha. |
| ✅ [#117, #118](ISSUES-RAW.md#issue-117) | Fee Payment show page par details (student name etc.) poori nahi aa rahi. |
| ✅ [#119](ISSUES-RAW.md#issue-119) | Paid voucher dobara pay ya dobara print na ho sake — currently possible lagta hai. |

### P2 — Project-Wide UI/UX Consistency Patterns

Dedupe karke ek-ek line item, lekin har jagah ke original numbers cite kiye hain.

| Pattern | Issue(s) |
|---|---|
| ✅ Pagination sirf tab dikhe jab records selected per-page se zyada hon; sahi page-count calculate ho | [#5, #56, #58, #61, #62](ISSUES-RAW.md#issue-5) |
| ✅ Page number bottom-right + pagination controls bottom-left, har table par | [#6](ISSUES-RAW.md#issue-6) |
| ✅ Filters card ke upar ek consistent background/style, har table ke upar (jaisa Attendance Class Report) | [#7](ISSUES-RAW.md#issue-7) |
| ✅ Status column + toggle switch + confirm modal, har jagah jahan active/inactive concept hai (badge khud clickable hai, separate button nahi) | [#8](ISSUES-RAW.md#issue-8) |
| 🟡 Consistent button color/icon system project-wide (delete=red hamesha, etc.), icon-only action columns with hover-to-reveal text — `RowAction`/`RowActions` shared component ban gaya + 17 files migrate ho gayi, ~11 files (MenuTable, Exam/Revaluations, Fee/Payments, Fee/Structures, Fee/Vouchers, Finance/Categories, Finance/PaymentMethods, Staff/Payroll, Staff/People, Transport, inventory/ItemsStock) abhi baaki | [#9, #10, #11](ISSUES-RAW.md#issue-9) |
| ✅ `#` serial column hamesha table ka pehla column ho | [#12](ISSUES-RAW.md#issue-12) |
| 🟡 Select2-style searchable dropdowns everywhere + filter-button-required pattern — naya `SearchableSelect.vue` component ban gaya, sirf 2/109 `<select>` swap hue (Fee/Payments/Index.vue), baaki follow-up chahiye; ✅ #111 (Add Custom Fee Head dropdown bug) fixed; #75 filter-button audit abhi baaki | [#39, #41, #75, #111](ISSUES-RAW.md#issue-39) |
| ✅ CNIC (13) / Phone (11) maxlength enforce everywhere | [#19](ISSUES-RAW.md#issue-19) |
| 🟡 Required fields poore bhare bina Submit/Create button disabled rahe — `useFormValidity.ts` composable ban gaya, 20 shared `components/forms/*` forms cover ho gaye; ~34 page-level Create forms (Fee, students, Finance, etc.) abhi baaki | [#81](ISSUES-RAW.md#issue-81) |
| ✅ Number input spinner arrows (up/down) hata do project-wide | [#68](ISSUES-RAW.md#issue-68) |
| 🔴 Campus → Class → Section → Session cascading select flow + auto-select for branch-scoped users + remembered active session everywhere — investigated, confirmed as a genuinely large effort (~22 pages each with independent cascade logic, no shared composable exists yet); needs its own dedicated multi-session tracked task, not folded into a quick P2 pass. See recommendation: build `useCascadingAcademicSelect.ts` composable first, roll out to attendance/Create, Exam/Papers/Create, students/Create, Fee/Vouchers/Generate as a pilot, then the remaining ~18 pages | [#47, #64, #82, #99, #100](ISSUES-RAW.md#issue-64) |
| ✅ Modal me duplicate Close(×) / Cancel controls — behavior consistent/clear ho (kept header X where a primary action exists, kept footer Close where it's the only control) | [#54, #55](ISSUES-RAW.md#issue-54) |
| ✅ Dark-mode background/contrast missing on specific dropdowns (grade system dropdown, revaluation dropdowns) | [#89, #97](ISSUES-RAW.md#issue-89) |
| ✅ Dark/Light mode should persist correctly per current mode when opening `/settings/appearance` | [#13](ISSUES-RAW.md#issue-13) |
| ✅ Fee Settings tabs pattern should match School Profile tabs pattern (consistent settings-page shell) | [#44](ISSUES-RAW.md#issue-44) |
| ✅ Sidebar open/close state persist across page refresh | [#124](ISSUES-RAW.md#issue-124) |

**P2 progress note:** #5, #6, #7, #8, #12, #13, #19, #44, #54, #55, #68, #89, #97, #111, #124 fully fixed; #9-11 and #39/#41/#81 partially fixed (commits `3b9768f`, `0428697`, `910f88c`, `88c0492`, `f980998`, `b360502`). Remaining: finish #9-11 on the ~11 flagged files, swap remaining ~107 `<select>` usages onto `SearchableSelect.vue` for #39/#41, audit #75 (filter button), wire `useFormValidity` into the ~34 page-level Create forms for #81. #47/#64/#82/#99/#100 (cascading selects) deliberately deferred as its own large tracked task — see line item above.

### P3 — Real Feature Gaps / Bigger Redesigns

| Issue(s) | Summary |
|---|---|
| [#29](ISSUES-RAW.md#issue-29) | Student profile page (`/students/1`) full redesign. |
| [#71](ISSUES-RAW.md#issue-71) | Student profile data display + UI still incomplete/poor (related to #29). |
| [#33](ISSUES-RAW.md#issue-33) | ID card redesign to actual Pakistani CNIC-card size. |
| [#35](ISSUES-RAW.md#issue-35) | Bulk ID card generation + direct print from filtered Students list. |
| [#32](ISSUES-RAW.md#issue-32) | Student print page — polish further. |
| [#94](ISSUES-RAW.md#issue-94) | Datesheet UI — avoid multi-page layout when many papers exist, needs proper layout redesign. |
| [#103](ISSUES-RAW.md#issue-103) | School timing/shift groups per class-group (with grace + break time), feeding into Attendance's Global Check-In/Check-Out defaults per class. |
| [#105](ISSUES-RAW.md#issue-105) | Checkout time input should stay disabled until that date's "off time" arrives. |
| [#107](ISSUES-RAW.md#issue-107) | Date-range attendance report (present/absent counts over a range) for a class. |
| [#115](ISSUES-RAW.md#issue-115) | Sibling bulk voucher creation + single bulk payment across multiple sibling vouchers. |
| [#116](ISSUES-RAW.md#issue-116) | Add a "Pay" button directly on the Vouchers list for direct payment. |
| [#22, #76](ISSUES-RAW.md#issue-22) | Enforce single-active-session and single-active-exam-system-wide, with optional cron job to auto-switch sessions on start/end dates. |
| [#38](ISSUES-RAW.md#issue-38) | Inline "Add Fee Head" via modal directly from Fee Structure create screen, auto-populating the dropdown without page reload. |
| [#25](ISSUES-RAW.md#issue-25) | Add Session: constrain calendar pickers to selected start/end year; searchable year dropdowns (2000–current, current+1). |
| [#26](ISSUES-RAW.md#issue-26) | Class/Section selection UX for "Assign Subjects to Class Sections" (visual state cues, enable/disable Load Subjects button). |
| [#27](ISSUES-RAW.md#issue-27) | New Enquiry — convert modal to full page (or well-structured grid modal) with 4-columns-per-row responsive layout; fix Father phone number field bug in Student create. |
| [#37](ISSUES-RAW.md#issue-37) | Fee Structure create via query params should auto-select campus/class/section/session inputs. |
| [#59](ISSUES-RAW.md#issue-59) | Auto-generate Session Name from Start Year + End Year instead of manual duplicate entry. |
| [#63](ISSUES-RAW.md#issue-63) | Fine Rules settings page UI cleanup — currently too bulky. |
| [#83](ISSUES-RAW.md#issue-83) | Default global paper start/end time to 9am–12pm. |
| [#84](ISSUES-RAW.md#issue-84) | Paper timing/dates set from Papers create should reflect on the relevant class's student & teacher portals. |
| [#85](ISSUES-RAW.md#issue-85) | Auto-suggest next class roll number on admission (editable, no repeats). |
| [#86](ISSUES-RAW.md#issue-86) | Bulk "Save Papers" button on Papers create should work properly. |
| [#91](ISSUES-RAW.md#issue-91) | Grace marks system — clarify/build where missing in Marking. |
| [#109, #110](ISSUES-RAW.md#issue-109) | Voucher generate page — months UI not responsive, needs a full dedicated test pass. |
| [#120](ISSUES-RAW.md#issue-120) | Fee Payments create — "Pay Amount" column purpose unclear + Voucher Dues table not responsive (columns/rows misaligned). |
| [#122](ISSUES-RAW.md#issue-122) | Fee Reports page needs a tab system. |
| [#36](ISSUES-RAW.md#issue-36) | Fee Structure create — add placeholders to every field + clarify Title field labeling (example hints). |
| [#123](ISSUES-RAW.md#issue-123) | Add red `*` next to Name on New Staff Member form (required-field indicator). |
| [#48](ISSUES-RAW.md#issue-48) | Add helper description text under "School is Active" toggle (mirroring "Public Website Active"). |
| [#49](ISSUES-RAW.md#issue-49) | Favicon should auto-update when logo changes. |
| [#50](ISSUES-RAW.md#issue-50) | Copyright footer should reflect current school name automatically. |
| [#51](ISSUES-RAW.md#issue-51) | Show a preview of newly-selected logo file before saving. |
| [#1](ISSUES-RAW.md#issue-1) | Favicon should update immediately when logo changes (browser tab, duplicate of #49's root cause — same fix). |
| [#17](ISSUES-RAW.md#issue-17) | Activity Log should capture every meaningful action (logins, enquiries added, etc.), not just current sparse coverage. |
| [#14](ISSUES-RAW.md#issue-14) | Activity Log page needs to become genuinely user-friendly (more/better columns) rather than developer-only raw data. |

### P4 — Needs an Explicit Product Decision Before Any Code Is Touched

| Issue(s) | Question (for owner, Roman Urdu) |
|---|---|
| [#4](ISSUES-RAW.md#issue-4) | **Question:** System mein "accountant" role hai — kya "director" naam ka role bhi hona chahiye ya already hai? Dono roles ki permissions set/confirm karni hain — please batayen kaunse roles honi chahiye aur unki exact permissions kya hon. |
| [#18](ISSUES-RAW.md#issue-18) | **Question:** Ye note adhoora hai ("Add New Campus ka jo modal ha us pr jo" — sentence incomplete chhoot gaya). Please poora karke batayen "Add New Campus" modal mein exactly kya issue/change chahiye. |
| [#20](ISSUES-RAW.md#issue-20) | **Question:** "Campus Type" button "user-friendly nahi" — exactly kya confusing lagta hai? Ek screenshot ya specific example de dein taake sahi fix decide ho sake. |
| [#21, #23](ISSUES-RAW.md#issue-21) | **Question:** Class/Section/Subject forms mein "Code" field ka kya purpose hai — remove kar dein hamesha ke liye, ya backend auto-generate kare (disabled dikhaye, edit ke waqt hi change ho sake)? Subject ke "Short Name" field ka bhi wahi treatment ho ya alag? |
| [#28](ISSUES-RAW.md#issue-28) | **Question:** Kaise ek student bina fee-structure select kiye admission system mein add ho gaya tha — kya ye validation gap hai jo band karni hai, ya koi valid legitimate scenario hai (jaise fee-structure baad mein assign hoti hai)? |
| [#30, #31](ISSUES-RAW.md#issue-30) | **Question:** "Father Information (Primary Guardian)" label ko generic "Information (Primary Guardian)" karke ek role-select dropdown add karna hai jisse jo role select ho wahi primary guardian ban jaye, aur baaki roles "Add Another Guardian" mein show/save hon — is exact flow ki confirmation chahiye pehle implement karne se pehle. |
| [#40](ISSUES-RAW.md#issue-40) | **Question:** Fee Head create form mein "Code" field remove karna hai ya system se auto-generate karna hai? (Same pattern jaisa #21/#23 mein Class/Section/Subject ke liye pucha gaya). |
| [#42, #43](ISSUES-RAW.md#issue-42) | **Question:** Fee Head "Category" aur "Frequency" dropdowns — Category select hote hi Frequency auto-select ho (user override kar sake) — confirm karein exact mapping rules. Aur "Category" list khud confusing/overlapping lag rahi hai (e.g. "Transport Fee" naam bhi, category bhi) — kya categories ko simplify/redesign karna hai? Naya list chahiye. |
| [#45](ISSUES-RAW.md#issue-45) | **Question:** Discount Type create mein "Approval" field kis role ko dena hai aur kyun — please specify kaunse role approval de sakte hain aur kis workflow ke liye. |
| [#46](ISSUES-RAW.md#issue-46) | **Question:** Fee Settings ke tabs (Fee Heads, Discount Types, Fine Rules, etc.) mein Add/Edit modal mein open hon (tab ke andar inline ke bajaye)? Confirm karein. |
| [#65, #66, #69](ISSUES-RAW.md#issue-65) | **Question:** Admission → Fee Structure ka poora UX confirm karna hai: (a) Fee Structure add karne ke baad wapis Admission form par previous data ke saath return karna hai [#65], (b) sirf active/selected fee-structure tab hi submit ho, baaki 2 tabs submit na hon [#66], (c) agar ek fee head manual-entry hai lekin admission ke waqt free karna ho + discount bhi apply karna ho, to ye 2 alag tabs mein hoga — jo tab active hai wahi submit ho, iska exact desired UX kya ho [#69]? |
| [#80](ISSUES-RAW.md#issue-80) | **Question:** Grade System modal ka text confusing hai ("Set Active / Set this grade system as active? / Yes, delete it! / Cancel" — "delete" wording activate-context mein galat lag rahi hai). Please ek screenshot bhejein taake exact modal aur uski sahi wording confirm ho sake. |
| [#96](ISSUES-RAW.md#issue-96) | **Question:** Kya published exam result ko un-publish karne ke liye Principal ka password/approval mandatory hona chahiye? Confirm karein ye feature chahiye ya nahi. |
| [#98](ISSUES-RAW.md#issue-98) | **Question:** "New Revaluation Request" ka exact maqsad/workflow kya hona chahiye — abhi kuch kaam nahi kar raha. Please poora flow describe karein (kaun request kare, kaun approve/review kare, kya outcome ho). |
| [#101](ISSUES-RAW.md#issue-101) | Owner ke apne 3 sawal (verbatim rakhe gaye hain, khud hi answer karne hain): **1st:** kya "Attendance List" aur "Mark Attendance" do sub-menus ko ek hi kar dein (dono mila kar mark + dekhna ek jagah ho)? **2nd:** kya attendance ka alag top-level module banana chahiye ya jaisa Staff ke andar set hai, waisa hi Student ke andar bhi rakh dein? **3rd:** har module mein alag "Settings" menu hone ke bajaye, ek central "School Setting" menu ho jahan har module (Student, Exam, etc.) ki settings ek jagah section-wise (click karne par page/modal open ho) mil jayen — ya alag-alag hi sahi hai? |
| [#106](ISSUES-RAW.md#issue-106) | **Question:** Attendance list mein "Locked/Unlocked" status ka exact purpose/workflow kya hona chahiye — kaun lock/unlock kar sake aur kis condition par? |
| [#108](ISSUES-RAW.md#issue-108) | **Question:** Kya "Attendance List", "Mark Attendance", aur "Student Reports" teeno ko merge karke ek hi menu/page bana dein (Student ke sub-menu ke andar, jisme leave bhi shamil ho), aur settings ko central "School Setting" (jaisa #101 mein idea diya) mein daal dein? (Ye #101 ka hi related follow-up hai — dono ka jawab saath milna chahiye). |
| [#113](ISSUES-RAW.md#issue-113) | **Question:** Kya Fee Voucher (`/fee/vouchers/1`) mein inventory records bhi shamil/reference hone chahiye, ya ye scope se bahar hai? Exact requirement clarify karein — abhi scope unclear hai. |

---

### Summary Counts

- **P0 (Security/Access):** 7 issue references (#2, #3, #15, #16, #52, #53, #112)
- **P1 (Broken functionality):** 26 issue references (#24, #34, #56, #57, #58, #60, #62, #67, #70, #72, #73, #74, #77, #78, #87, #88, #90, #92, #93, #95, #102, #111, #117, #118, #119, #121)
- **P2 (UI/UX consistency, deduped to 15 line items):** 34 issue references (#5, #6, #7, #8, #9, #10, #11, #12, #13, #19, #39, #41, #44, #47, #54, #55, #61, #64, #68, #75, #81, #82, #89, #97, #99, #100, #111, #124 — note #61 and #111 also appear once each under P1/P2 groupings where the same numbered issue mixes a functional bug and a pattern; kept under both with distinct sub-aspects.)
- **P3 (Feature gaps / redesigns):** 33 issue references (#1, #14, #17, #22, #25, #26, #27, #29, #32, #33, #35, #36, #37, #38, #48, #49, #50, #51, #59, #63, #71, #76, #83, #84, #85, #86, #91, #94, #103, #105, #107, #109, #110, #115, #116, #120, #122, #123 — count includes closely related duplicates grouped together)
- **P4 (Needs owner decision):** 19 issue references across 15 question line-items (#4, #18, #20, #21, #23, #28, #30, #31, #40, #42, #43, #45, #46, #65, #66, #69, #80, #96, #98, #101, #106, #108, #113)

(Some issue numbers legitimately appear in more than one bucket because the owner's single note bundles both a bug and a bigger ambiguous ask — e.g. #96 has a P1-ish functional check and a P4 decision, filed under P4 since the decision blocks any fix. #61 has both a P1 toggle-bug aspect, filed under P1, and a P2 pagination/close-cancel pattern aspect, filed under P2. Total unique original issue numbers referenced across all buckets: 124, including the deliberately-skipped #79 gap and the split #52/#53 and #54/#55 pairs as given.)
