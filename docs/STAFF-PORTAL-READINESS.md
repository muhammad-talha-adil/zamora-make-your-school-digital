# Teacher / Staff Portal Readiness

Status: **No dedicated staff/teacher portal exists.** The backend permission model for
`staff.view.own` is more complete than the student side (real routes exist for own
profile/attendance/salary/leave), but there is zero dedicated UI, no menu entry pointing a teacher
at any of it, and no convenience routes - a teacher would have to already know their own numeric
`staffProfile` ID and type a URL by hand. This document is self-contained.

## 1. Sidebar / menu situation

- `app/Http/Middleware/HandleInertiaRequests.php:56-58` filters `Menu::active()` by
  `$menu->role === null || $user->hasRole($menu->role)`.
- `database/seeders/MenuSeeder.php` never sets `role` on any menu row (grep for `'role'` in that
  file returns nothing), so every authenticated user - including a plain `teacher` - sees the
  **entire admin sidebar**.
- The "Staff" menu (`MenuSeeder.php:377-393`) has exactly one child: "Dashboard" ->
  `/staff` - the admin staff dashboard. **No menu item points a teacher at their own profile,
  attendance, salary, or leave page.** Even though the backend routes exist (see below), there is
  no UI path to reach them without manually typing an ID into the URL bar.
- The "Staff -> Teaching" screens (`TeacherAssignmentController`, see below) have no menu entry
  either.

## 2. Route reachability for `teacher` / plain staff

Teacher permissions (`database/seeders/RolesSeeder.php:102-111,185-189`):

```
portal.teacher.access, students.view,
attendance.view, attendance.mark, attendance.edit, attendance.reports,
exam.view, exam.paper.view, exam.paper.manage,
exam.marks.enter, exam.result.view,
academics.timetable.view, staff.view.own
```

Non-teaching staff get a smaller `staffBasics` set (`RolesSeeder.php:113-117`):
`portal.staff.access, staff.view.own`.

### Own profile - works, but needs a known ID
- `GET /staff/people/{staffProfile}` (`StaffProfileController::show`) is gated
  `permission:staff.view|staff.view.own` (`routes/staff.php:87`). A teacher with only
  `staff.view.own` can open **their own** profile if they know their `staffProfile` ID - there is
  no `/staff/me` alias that resolves it automatically from the logged-in user.

### Own attendance - works, but needs a known ID
- `GET /staff/attendance/people/{staffProfile}` and `.../summary` are gated
  `permission:staff.attendance.view|staff.view.own` (`routes/staff.php:157-159`). A teacher can see
  their own attendance record/summary this way, again only by URL with the right ID.
- Reuses `WorkingDayCalculator`/`LateArrivalResolver` via `StaffAttendanceService`, so the
  underlying data logic is already shared with the admin register - no separate computation needed
  for a portal.

### Own salary slip - works, but needs a known ID
- `GET /staff/salary/people/{staffProfile}` (`StaffSalaryController::index`) is gated
  `permission:staff.salary.manage|staff.view.own` (`routes/staff.php:177`). `staff.salary.manage`
  is deliberately kept separate from `staff.manage` (per the comment at `routes/staff.php:24-26`:
  "a campus admin may hire, edit and mark attendance without seeing what anybody is paid"), and
  `staff.view.own` is the second acceptable permission - so a teacher genuinely can read their own
  salary components today via direct URL. There is no "download my salary slip as PDF" action
  though - `StaffSalaryController::index` only returns component data, not a formatted slip.

### Own leave & leave balance - works, and can self-apply
- `GET /staff/leaves/people/{staffProfile}` and `/balance` are gated
  `permission:staff.attendance.view|staff.view.own` (`routes/staff.php:186-190`).
- `POST /staff/leaves/people/{staffProfile}` (apply) is gated
  `permission:staff.view.own|staff.manage` (`routes/staff.php:192`), and
  `PATCH /staff/leaves/{leave}/cancel` similarly accepts `staff.view.own` (`routes/staff.php:196`).
- `StaffLeaveController::apply()` calls `Gate::authorize('applyForLeave', $leave->staffProfile)`
  (`app/Http/Controllers/Staff/StaffLeaveController.php:93`) - a real, working per-record
  authorization check, not just a route-level permission string.
- **This is the one genuinely functional self-service action on the staff side**: a teacher can
  view their leave balance and submit/cancel their own leave application - but again, only by
  already knowing their own `staffProfile` ID and hitting the right URL/API call manually; there is
  no page wired up for it.

### My teaching schedule - missing permission coverage
- `TeacherAssignmentController` routes (`routes/staff.php`, "Phase 4"): `/staff/teaching` (page),
  `/staff/teaching/list` (index), `/staff/teaching/sections`, `/staff/teaching/who-can-teach` are
  **all gated by `permission:staff.view`** only - **not** `staff.view.own`.
- A plain `teacher` role only holds `staff.view.own`, not `staff.view` - so **a teacher cannot see
  their own assigned classes/subjects through this controller at all**. Their effective teaching
  width only shows up implicitly through what `attendance.*`/`exam.*` widths let them mark/enter
  (via width-checking helpers like `covers()` in the exam/attendance policies), never through a
  "my schedule" screen.
- This is a genuine functional gap, not just a missing UI: the permission model itself needs a
  `staff.view.own`-accepting variant of these routes (or a new dedicated
  `TeacherAssignmentService` query scoped to the caller) before a "my schedule" page could work.

### Timetable
- `academics.timetable.view` is seeded for teachers (`RolesSeeder.php:109`) but, as on the
  student/guardian side, **no timetable feature exists anywhere in the codebase** (`grep -rli
  timetable app resources/js routes database/migrations` finds only exam-paper and
  staff-assignment code). Complete placeholder permission; needs the feature built from scratch.

### No `/staff/me` convenience route
- `grep` across `app/Http/Controllers/Staff/*.php` and `routes/staff.php` for `staff/me`,
  `->staffProfile` auto-resolution, or any `Auth::user()->staffProfile` shortcut returns nothing.
  Every "own" route requires the numeric `{staffProfile}` parameter in the URL; nothing resolves it
  from the logged-in user automatically anywhere in the backend.

## 3. Dedicated landing page?

- `app/Http/Controllers/PageController.php::dashboard()` (`:46-88`) renders the same `Dashboard.vue`
  (admin-wide stats: active students/staff, fee summary, attendance today, upcoming exam papers,
  low stock) for a teacher as for a `campus_admin`. No teacher-specific dashboard (e.g. "my classes
  today", "my leave balance", "my next salary date") exists anywhere in `resources/js/pages`.

## 4. Login redirect

- `config/fortify.php:76`: `'home' => '/dashboard'` is the single global post-login redirect for
  every role. No `routes/auth.php` or other role-based redirect logic exists. A teacher lands on
  the same admin `/dashboard` as everyone else, with no route sending them to a staff self-service
  area (e.g. `/staff/self`).

## 5. "Portal" references found in code

- `database/seeders/RolesSeeder.php:104,115` seeds `portal.teacher.access` (teacher role) and
  `portal.staff.access` (non-teaching staff / campus_admin, `RolesSeeder.php:166`) permissions.
  `grep -rn "portal\.teacher\.access\|portal\.staff\.access"` across `app/`, `routes/`,
  `resources/js/` returns **nothing** - neither permission is checked by any route, controller, or
  middleware. Both are dead placeholders, presumably seeded in anticipation of a portal that was
  never built.
- No controller, route file, or Vue directory anywhere is named or namespaced for a staff portal.

## 6. Reusable as-is

- **Real, working routes already accepting `staff.view.own`** (need only a menu link + a
  convenience "me" wrapper, not new backend logic):
  - `GET /staff/people/{staffProfile}` - own profile (`routes/staff.php:87`)
  - `GET /staff/attendance/people/{staffProfile}` + `/summary` - own attendance
    (`routes/staff.php:157-159`)
  - `GET /staff/salary/people/{staffProfile}` - own salary (`routes/staff.php:177`)
  - `GET /staff/leaves/people/{staffProfile}` + `/balance`, `POST .../{staffProfile}` (apply),
    `PATCH /{leave}/cancel` - own leave + self-application (`routes/staff.php:186-196`)
- **Service classes already doing the real work**, callable with a resolved own-`staffProfile`
  instead of a route parameter: `App\Services\Staff\StaffAttendanceService`,
  `App\Services\Staff\StaffLeaveService` (used by `StaffLeaveController`).
- **A genuine per-record authorization check to copy**: `Gate::authorize('applyForLeave',
  $leave->staffProfile)` in `StaffLeaveController::apply()` - proves the leave-application flow is
  already safe for direct self-service use, not just a route-level string check.
- **`portal.teacher.access` / `portal.staff.access` permissions** - already seeded, unused, ready
  to gate new `/staff/self` (or `/portal`) routes with zero permission-model change.
- **The student-side `ownStudents()`/`assertMayActFor()` pattern** in
  `app/Http/Controllers/StudentLeaveController.php:172-220` is a directly transferable template for
  writing a `staffProfile` -> `auth()->user()` resolver (e.g. `$user->staffProfile` instead of
  `$user->student`/`$user->guardian`).

## 7. What's missing for a real staff/teacher portal

- **`/staff/me` (or `/portal`) convenience routes** that resolve `auth()->user()->staffProfile`
  server-side, wrapping the existing `staff.view.own`-gated controllers
  (`StaffProfileController::show`, `StaffAttendanceController::index/summary`,
  `StaffSalaryController::index`, `StaffLeaveController::index/balance/apply/cancel`) so a teacher
  never needs to know or type their own numeric ID.
- **A `staff.view.own`-accepting (or caller-scoped) variant of `TeacherAssignmentController`**
  (`/staff/teaching*` routes, currently `staff.view`-only, `routes/staff.php` "Phase 4") so a
  teacher can see their own assigned classes/subjects - this is a genuine backend gap, not just a
  UI one.
- **A menu entry / dedicated nav pointing teachers at their own pages** - today the "Staff" menu
  (`MenuSeeder.php:377-393`) has only an admin "Dashboard" child; nothing links to
  profile/attendance/salary/leave-self-service, even though the routes exist.
- **A salary-slip formatted download/print action** - `StaffSalaryController::index` returns raw
  component data, not a printable/downloadable payslip.
- **A genuinely new timetable/teaching-schedule feature** for "my classes today" -
  `academics.timetable.view` is an unused placeholder with no underlying model.
- **A staff-specific dashboard** (my leave balance, today's classes, next salary date) - currently
  every role gets the same admin `Dashboard.vue`.
- **Role-based post-login redirect** to a staff-self area (e.g. `/staff/self`), and a **dedicated
  portal layout/shell** distinct from the admin `AppLayout`.

## Recommendation (build order)

1. Add `/staff/me/*` convenience routes/controller methods that resolve
   `auth()->user()->staffProfile` and delegate to the existing `staff.view.own`-gated controllers -
   zero new authorization logic needed, just removes the "must already know your ID" blocker. This
   is the highest-value, lowest-risk first step since almost everything else already works
   server-side.
2. Add a `staff.view.own`-scoped (or self-resolving) version of `TeacherAssignmentController` so
   teachers can see their own teaching schedule - the one real backend gap found.
3. Wire a "My Profile" section into the sidebar (behind `Menu.role` filtering once that's populated,
   see the student-portal doc) linking to the new `/staff/me/*` routes.
4. Build a lightweight staff self-service dashboard (leave balance, today's schedule, latest
   payslip) and route teachers there after login.
5. Add a formatted, downloadable salary-slip view/PDF on top of the existing
   `StaffSalaryController::index` data.
6. Defer the full class-timetable feature - independent, larger build, not blocking the rest.
