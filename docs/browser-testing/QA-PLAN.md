# QA plan: kya kab test hoga (hierarchy + pages)

Rule: har step tabhi "SATISFIED" jab browser report mein koi FAIL na ho. Uske baad hi agla step. Pichla step badle to sirf uska chhota re-check.
Har prompt mein ye lazmi: campus/phone auto-fill, negative number nahi (min 0), friendly inline messages (fresh form par nahi), kam se kam clicks, hisaab sahi, 500/console error nahi, 400px par layout, double-submit nahi, owner login (owner@school.com / 123456).

## Order

| # | Step | Status | Prompt | Depends on |
|---|------|--------|--------|-----------|
| 1 | Fee (structure, voucher, payment, reports) | 01r re-check pending | 01r Part 1 | - |
| 2 | Staff admin (people, setup, payroll basics) | 01r Part 2 pending | 01r Part 2 | Settings basics |
| 3 | Settings and school setup | not tested properly | 02 (update before use) | - (sab ka base) |
| 4 | Attendance (student + staff) | not started | 03 | 2, 3, Students |
| 5 | Staff payroll (attendance se salary) | not started | 04 | 2, 4 |
| 6 | Exams | not started | 05 | 3, 4, Students |
| 7 | Timetable | not started | 06 | 2, 3 |
| 8 | Inventory (stock, student charge) | not started | 07 | Fee |
| 9 | Transport | partly (Fee dues only) | 08 | Fee, Students |
| 10 | Finance (income, expense, cash book) | old 05-finance.md | 09 | Fee |
| 11 | Roles/permission sweep | not started | 90 | sab |
| 12 | Portals (student/guardian/staff) | last, abhi nahi | 91 | sab |

Suggestion: Settings (3) ko Staff (2) ke saath ya foran baad karein, kyunke Attendance/Exams sab Classes/Sections/Sessions par chalte hain.

## Pages per step

### 1 Fee
- /fee/dashboard
- Setup: /fee/settings, /fee/heads, /fee/structures (+create), /fee/discount-types, /fee/settings/fine-rules, /fee/discount-approvals
- Vouchers: /fee/vouchers, /fee/vouchers/generate, /fee/vouchers/unpaid/list, /fee/vouchers/overdue/list
- Payments: /fee/payments, /fee/payments/create, family (/fee/payments/siblings)
- Reports: /fee/reports/collection, /fee/reports/outstanding, /fee/reports/defaulters, /fee/reports/payment-methods
- Student profile Fee card + Ledger (/students/{id})

### 2 Staff (admin)
- /staff (+list, id-cards), /staff/create
- /staff/settings, /staff/document-types, /staff/leaves/types
- /staff/teaching (+sections, exams, who-can-teach)
- /staff/payroll, /staff/attendance
- Staff portal (/staff/me) abhi nahi.

### 3 Settings
- /settings/school-hub, /settings/school-profile
- /settings/campuses, /settings/campus-types
- /settings/school-classes, /settings/sections, /settings/subjects, /settings/class-subjects
- /settings/sessions, /settings/months, /settings/exam-types
- /settings/menu-settings, /settings/appearance, /settings/activity-log
- /settings/profile, /settings/password, /settings/two-factor, /settings/subscription

### Students (Fee ke saath ho chuka, baqi baad mein ek chhota round)
- /students, /students/create, /students/enquiries, /students/promotion, /students/id-cards, /students/document-types, /student-leaves/pending

### 4 Attendance
- /attendance/hub, /attendance/dashboard, /attendance/create (mark), /attendance/class/report, /attendance/scan
- /attendance/settings (+holidays, leave-types)
- /staff/attendance (+statuses), /student-leaves/pending

### 5 Staff payroll
- /staff/payroll, payslip, attendance se deductions, leave types

### 6 Exams
- /exams/dashboard, /exams/settings, /exams/grade-scales, /settings/exam-types
- /exams/create, /exams/list, /exams/papers, /exams/registrations
- /exams/marking (+grid, grace), /exams/results, /exams/result-sheet, /exams/annual-results, /exams/revaluations

### 7 Timetable
- /timetable, /timetable/periods

### 8 Inventory
- /inventory, /inventory/types, /inventory/items, /inventory/suppliers, /inventory/purchases, /inventory/purchase-returns
- /inventory/stocks, /inventory/adjustments, /inventory/student-inventory, /inventory/returns, /inventory/settings

### 9 Transport
- /transport (routes, vehicles, drivers, student assignment, Generate Dues)

### 10 Finance
- /finance, /finance/receive-payment, /finance/make-payment, /finance/transactions
- /finance/categories, /finance/payment-methods, /finance/student-account-statement
- /finance/reports/cash-book, /finance/reports/income, /finance/reports/expense

### 11 Roles sweep
- Har role ka login: kya dikhna chahiye, baqi URL par 403 (docs/TEST-LOGINS.md)

### 12 Portals
- /portal, /portal/fees, /portal/attendance, /portal/exams, /portal/timetable, /staff/me

## Har step ka tareeqa
1. Prompt browser mein chalao, report yahan paste karo.
2. Saare findings ek hi agent se ek pass mein fix (root cause + invariant tests).
3. Build, commit, push. Agla prompt mein pichle FAIL + NOT TESTED items shamil.
4. Report mein SATISFIED aaye to TEST-LOG.md mein row bharo aur agla step.

## Deferred (bana nahi, par bhoolna nahi)
- Staff: Departments, Shifts, Roles pages (Staff mein hain hi nahi).
- Transport: alag sidebar menu/submenus, Dues tab mein list, form hamesha khula (tabs), page structure.
- Fee: Inventory due + real Print dialog (R10/R3) abhi NOT TESTED; Fee dashboard vs Outstanding total cross-check.
- Staff: percent field / deductions field staff form mein nahi; Documents/photo upload (Document Types 0) NOT TESTED.
- Students: campus field Create mein nahi (header se infer); Section auto sirf 1-section case.
- Settings: poora 02-settings-setup.md (abhi tak nahi chala).
- Dashboard overdue widget voucher status se, plain-text Campus filter Defaulters/Outstanding, structure list grouping, timezone, stale getForDropdown cache.

## Ek module = ek round rule
Har module ke liye pehle poora sweep: agent code mein hi pehle woh checks chalaye jo browser karega (har form: required, negative, duplicate, max, future date, campus scope, 403, phone/CNIC format, inline messages, placeholders, double-submit, 500 test) aur invariant tests likhe. Browser report ke baad ek hi fix pass. Maqsad: module ek ya do round mein SATISFIED.

## Deferred (from 01r/01s)
- Transport submenu proposal: Dashboard, Vehicles, Routes & Stops, Assignments, Monthly Dues (with list), Expenses, Reports.
- Fee Print dialog.
- Transport "added to N vouchers" case (merged dues on a voucher that already has payments).
- Logo upload, password change, menu toggle effect, pagination.
