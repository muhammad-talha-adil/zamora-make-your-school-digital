# Student / Guardian Portal Readiness

Status: **No dedicated student/guardian portal exists.** "Own record" access today is a set of
scattered permission/policy checks bolted onto the same admin-facing screens, not a separate
self-service experience. This document is self-contained; it covers everything a
`student`/`guardian` account can and cannot do today, and what's needed to turn that into a real
portal.

## 1. Sidebar / menu situation

- `app/Http/Middleware/HandleInertiaRequests.php:56-58` builds the shared `menus` prop by filtering
  `Menu::active()` where `$menu->role === null || $user->hasRole($menu->role)`.
- `database/seeders/MenuSeeder.php` (573 lines) **never sets `'role' => ...` on any `Menu::create()`
  call** - every menu row's `role` column is `null`.
- Result: a `student` or `guardian` account sees the **entire admin sidebar** - Students, Exams,
  Attendance, Staff, Transport, Finance, Inventory, Settings - identical to a `campus_admin`. There
  is no menu curation by role at all, despite the `Menu.role` column existing for exactly this
  purpose (currently only ever intended for the developer-only Subscription page, per the comment
  at `HandleInertiaRequests.php:53-55`, and even that page isn't in the seeder).
- The one menu item genuinely relevant to a family is "Student Leaves"
  (`MenuSeeder.php:236-243`, `url: '/student-leaves/page'`), which every role sees since it's
  unfiltered.

## 2. Route reachability for student / guardian

Permissions (`database/seeders/RolesSeeder.php:203-231`) for both `student` and `guardian` (they
are identical - guardians see exactly what their student sees):

```
portal.student.access, students.view.own, attendance.view.own,
exam.result.view.own, exam.paper.view, fee.view.own,
academics.timetable.view, transport.view.own
```

### Fee vouchers / payment history
- `GET /fee/vouchers/{voucher}` (`show`/`print`/`challan`) - **works**. Gated by
  `permission:fee.view|fee.voucher.view|fee.view.own` (`routes/fee.php:137-138,165-168`), and
  `FeeVoucherPolicy::view()` genuinely checks ownership.
- `GET /fee/vouchers` (`index`, the list) - **broken for students**. Gated by
  `permission:fee.view|fee.voucher.view` only (`routes/fee.php:120-121`), and
  `FeeVoucherPolicy::viewAny()` (`app/Policies/Fee/FeeVoucherPolicy.php:26-29`) only accepts
  `fee.view`/`fee.voucher.view` - **not** `fee.view.own`. Same gap in
  `FeePaymentPolicy::viewAny()` (`app/Policies/Fee/FeePaymentPolicy.php:31-34`).
- **Consequence**: a student/guardian cannot browse "my fee vouchers" or "my payment history" at
  all - they can only open a voucher if handed a direct link/ID (matches the comment at
  `app/Http/Controllers/Fee/FeeVoucherController.php:686`: "a parent opening a challan from
  WhatsApp has no portal login").

### Exam results
- Same broken pattern as fees. `routes/exam.php:45-47` lets a student load the results **list page
  shell** (middleware accepts `exam.result.view|exam.result.view.own`), but the data-fetch endpoint
  calls `$this->authorize('viewAny', ExamResultHeader::class)`, and
  `ExamResultHeaderPolicy::viewAny()` (`app/Policies/Exam/ExamResultHeaderPolicy.php:30-32`) only
  accepts `exam.result.view`/`exam.marks.enter` - **not** `.view.own`. The shared admin "Results"
  list therefore 403s for a student even though route middleware let them past the door.
- The one thing that **does** work: `GET /exams/results/{resultHeaderId}/card`
  (`ExamReportCardController::card()`, `app/Http/Controllers/Exam/ExamReportCardController.php:38-46`)
  calls `authorize('view', $header)` -> `ExamResultHeaderPolicy::view()` ->
  `isTheirOwn()`/`viewOwn()` (`ExamResultHeaderPolicy.php:35-53`), which is a real ownership check.
  But it renders a raw Blade print view (`exam.result-card`), not an Inertia page, and there is
  **no way for a student to discover the `resultHeaderId`** since the list endpoint is blocked.
- `results.student` route (`GET /exams/students/{studentId}/results/{examId}`,
  `routes/exam.php:145`, gated `exam.result.view|exam.result.view.own`) maps to
  `ExamResultController::studentResult()` - **a stub**:
  `app/Http/Controllers/Exam/ExamResultController.php:240-243` just returns
  `{"message":"Student result", ...}` JSON. Not implemented.
- Exam date-sheet (exam schedule, not a class timetable): `GET /exams/{examId}/datesheet`, gated
  `exam.paper.view|exam.result.view.own` (`routes/exam.php:113-115`) - reachable.

### Attendance history
- `attendance.view.own` is seeded (`database/seeders/PermissionsSeeder.php:98`) but **no route or
  controller consumes it anywhere** - `routes/attendance.php` only exposes admin-scoped
  `attendance.view`/`attendance.edit` endpoints (register `show`, `studentReport`,
  `classReport`). There is no "my attendance history" endpoint for a student/guardian at all. This
  is a straight gap: permission exists, nothing to attach to.

### Leave application - the one genuinely working self-service flow
- `app/Http/Controllers/StudentLeaveController.php` + `routes/attendance.php:68-77`
  (`Route::prefix('student-leaves')`).
- `StudentLeaveController::page()` (`:34-61`) branches: staff with `attendance.view` see all
  students; everyone else (student/guardian) gets `ownStudents()` (`:172-183`), which resolves
  `$user->student` or `$user->guardian->students()->get()` - **no route parameter needed to find
  "my own" record**, a good pattern to copy.
- `assertMayActFor()` (`:207-220`) genuinely checks the caller is the student themselves
  (`$student->user_id === $user->id`) or a linked guardian
  (`$student->guardians()->whereHas('user', ...)`) before allowing `store()`/`index()`.
- `resources/js/pages/attendance/StudentLeaves/Index.vue` shows the "New Application" button
  unconditionally (`:292-295`, not gated by `canViewPending`), so a student/guardian really can
  submit their own leave application through this screen.
- Caveat: it's the **same shared admin screen** used by staff - approve/reject columns are just
  hidden via `v-if="props.canDecide"` (`Index.vue:387,409,426-427`) - reached via the same
  "Student Leaves" sidebar item every role sees.

### Timetable
- `academics.timetable.view` is seeded (`RolesSeeder.php:212,228`) but **no timetable feature
  exists anywhere in the codebase** - a repo-wide search for "timetable" across `app/`,
  `resources/js/`, `routes/`, `database/migrations/` turns up only exam-paper and staff-assignment
  code, no timetable model/controller/route/Vue page. This permission is a complete placeholder;
  the feature needs to be built from scratch, not just exposed.

### Own student profile
- No `{student}/show` detail route exists even for admins (only `edit`, `print`,
  `leaving-certificate`, `siblings`, etc. in `routes/students.php`) - so there's nothing today
  resembling a "my profile" page for a student either, admin-side or portal-side.

## 3. Dedicated landing page?

- `app/Http/Controllers/PageController.php::dashboard()` (`:46-88`) renders **one** Inertia
  component, `Dashboard.vue`, for every role, with cross-module admin stats
  (`active_students`, `fee_summary`, `attendance_today`, `upcoming_exam_papers`, `low_stock_count`).
  No role branching. No separate "MyDashboard"/portal component exists anywhere in
  `resources/js/pages`.

## 4. Login redirect

- `config/fortify.php:76`: `'home' => '/dashboard'` - a single global redirect target used for
  every account after login/password-reset. No role-based redirect logic exists anywhere
  (`routes/auth.php` doesn't even exist as a custom file; Fortify defaults are used as-is).
  Students and guardians land on the same admin `/dashboard` as everyone else.

## 5. "Portal" references found in code

`grep -rni "portal"` across `app/`, `resources/js/`, `routes/` turns up **no portal routes,
controllers, or Vue pages** - only prose comments describing the *absence* of a separate portal:
- `app/Http/Controllers/StudentController.php:120` - "...including the student portal's" (about
  login credentials).
- `app/Http/Controllers/StudentLeaveController.php:22-24` - "There is no separate guardian
  portal - a guardian signs in to the student's portal - so the same endpoint serves both."
- `routes/attendance.php:63-64`, `routes/fee.php:24` - same "no separate portal" framing.
- `database/seeders/RolesSeeder.php:207,223` seeds a `portal.student.access` permission, but
  `grep -rn "portal\.student\.access"` across `app/`, `routes/`, `resources/js/` returns
  **nothing** - it's never checked anywhere. A dead placeholder for a portal gate that doesn't
  exist yet.

## 6. Reusable as-is

- **Roles/permissions**: `student`/`guardian` roles and their `.view.own` grants already exist -
  `database/seeders/PermissionsSeeder.php:82-181` (`students.view.own`, `attendance.view.own`,
  `exam.result.view.own`, `fee.view.own`, `transport.view.own`).
- **Policies with real ownership checks**: `app/Policies/Exam/ExamResultHeaderPolicy.php`
  (`isTheirOwn`/`viewOwn`), `app/Policies/Fee/FeeVoucherPolicy.php`,
  `app/Policies/Fee/FeePaymentPolicy.php`, `app/Policies/StudentPolicy.php`,
  `app/Policies/TransportVehiclePolicy.php`.
- **The working leave-application pattern**: `StudentLeaveController::ownStudents()` /
  `assertMayActFor()` (`app/Http/Controllers/StudentLeaveController.php:172-220`) - resolve
  `$user->student` / `$user->guardian->students()`, no route parameter required. This is the
  template a portal's other "my own" controllers should follow.
- **Print-ready views to embed as-is** once given a "my own" resolver: the exam result-card Blade
  view (`ExamReportCardController::card()`) and the fee voucher `show`/`print`/`challan` views
  (`FeeVoucherController`).
- **`portal.student.access` permission** - already seeded, unused, ready to become the actual
  route-gate for new `/portal` routes.

## 7. What's missing for a real student/guardian portal

- **Two `viewAny()` policy gaps** blocking any list-based self-service:
  - `FeeVoucherPolicy::viewAny()` (`app/Policies/Fee/FeeVoucherPolicy.php:26-29`) and
    `FeePaymentPolicy::viewAny()` (`app/Policies/Fee/FeePaymentPolicy.php:31-34`) need to accept
    `fee.view.own` (scoped to the caller's own student), or a portal needs its own non-shared list
    endpoint.
  - `ExamResultHeaderPolicy::viewAny()` (`app/Policies/Exam/ExamResultHeaderPolicy.php:30-32`)
    needs to accept `exam.result.view.own` similarly, or again a dedicated portal query.
- **A missing attendance-history route/controller** - `attendance.view.own` has nothing to attach
  to; needs a new endpoint (e.g. `GET /portal/attendance`) querying the caller's own student
  attendance.
- **`ExamResultController::studentResult()` stub** (`app/Http/Controllers/Exam/ExamResultController.php:240-243`)
  needs a real implementation.
- **A genuinely new timetable feature** - `academics.timetable.view` has no model, controller,
  route, or Vue page to expose; this is new-build, not just a permission fix.
- **Lightweight "my own" controllers/routes with no ID in the URL** - e.g. a `/portal` (or `/my`)
  prefix that resolves `auth()->user()->student` / `->guardian` server-side and calls existing
  services (`FeeVoucherController`, `ExamReportCardController`, `StudentLeaveService`), instead of
  requiring `{voucher}` / `{resultHeaderId}` / `{student}` IDs the family has to already know.
- **Discoverability fixes for results/vouchers**: build "my results"/"my vouchers" index queries
  (e.g. `ExamResultHeader::where('student_id', $myStudentId)`,
  `FeeVoucher::where('student_id', $myStudentId)`) rather than reusing the admin `index()` actions,
  which reject `.view.own` today.
- **Menu curation**: populate `Menu.role` (column already exists, `app/Models/Menu.php:20`) so
  student/guardian accounts stop seeing Finance/Inventory/Staff/Settings, or build a separate
  portal menu entirely.
- **Role-based post-login redirect**: `config/fortify.php`'s `home` is a single global constant;
  routing `student`/`guardian` to `/portal` needs new logic (custom Fortify `LoginResponse` or
  middleware).
- **A dedicated portal layout/shell** distinct from the admin `AppLayout`/sidebar.

## Recommendation (build order)

1. Fix the two `viewAny()` policy gaps (fee vouchers, fee payments, exam results) - smallest
   change, unblocks list-based self-service immediately without any new UI.
2. Build the "my own" resolver layer (student/guardian equivalent of
   `StudentLeaveController::ownStudents()`) as a small trait/service, then add `/portal` routes for
   fee vouchers, exam results, and attendance history that call existing services with the
   resolved own-`student_id` instead of a route parameter.
3. Implement the attendance-history endpoint (permission already seeded, nothing built).
4. Add a dedicated portal layout + curated menu (`Menu.role = 'student'`/`'guardian'`) and a
   role-based post-login redirect to `/portal`.
5. Only then invest in the class timetable feature - it's a bigger, independent build (no
   underlying data model exists yet) and isn't blocking the rest of the portal.
