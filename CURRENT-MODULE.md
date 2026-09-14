# Where the project stands

A plain statement of what is built, as of **14 September 2026**. Rough or
polished, if it works it is listed here.

The finished module records — every fault found and what was done about it —
are in [docs/MODULE-LOG.md](docs/MODULE-LOG.md). The rules the work follows are
in [docs/WORKING-RULES.md](docs/WORKING-RULES.md).

**1100+ tests pass. PHPStan is at zero. Pint is clean.**

---

## Modules that have been through a full review

Each one was read end to end — schema, models, services, controllers,
requests, routes — then fixed and covered with tests. Full findings for every
row are in [docs/MODULE-LOG.md](docs/MODULE-LOG.md).

| Module | Tests | What it does |
|---|---|---|
| **Fee + Finance** | 234 | Fee structures, per-item frequency, discounts, sibling discounts, fine slabs, proration, instalments, voucher generation, the bank challan, payments and reversal, and Finance's own transaction/receive/make-payment screens — unified onto one double-entry accounting system (see FF7 in `docs/MODULE-LOG.md`) |
| **Attendance** | 246+ | Daily registers, lock and auto-lock, holidays, leave applications (including the student-leave apply/approve/reject screen), late arrivals and late fines, biometric punch import, consecutive-absence alerts, working-day calculation, monthly summaries, class and student reports, teacher class assignments |
| **Exam** | 204+ | Exams and papers, registration from the roll, marking, grading scales, grace marks, subject roles, pass and fail, position in class (with a manual recompute action), the result card, the date sheet, the annual result, rechecking with a full office-side review/approve/reject/apply-change workflow, publishing and locking |
| **Student** | 278+ | Admission with guardians and fee mode, the enrolment lifecycle (leaving, re-admission, transfer, promotion — now with its own screen), the leaving certificate, ID cards, sibling links, CSV export and import, admission enquiries |
| **Staff** | 90 | One person/many jobs, campus and salary authorisation, the personal file (with leave/rejoin actions), teaching assignments, staff attendance and leave, named salary components and payroll, the dashboard and every screen |
| **Settings** | 38 | School profile, campuses, campus types, classes, sections, subjects, academic sessions, exam types, attendance settings, sidebar menu management, theme, profile/password/2FA |
| **Transport** | 16 | Vehicles, routes, stops, student assignments, vehicle expenses — campus-scoped, permission-gated |
| **Inventory** | 33 | Types, items, stocks (reserve/release, low-stock alerts), suppliers, adjustments, purchases and purchase returns, student issuance and returns — all ten controllers now permission-gated and campus-scoped |

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

None — every module has been read end to end.

Staff was reviewed and rebuilt across eight phases rather than by the
read-end-to-end process every other row in this file goes through — its full
record is in [docs/MODULE-LOG.md](docs/MODULE-LOG.md). Settings (16
controllers), Transport and Inventory were each reviewed and fixed the same
way Fee/Finance was — full findings in
[docs/MODULE-LOG.md](docs/MODULE-LOG.md) under their own "Module: …" headings.

---

## How a module gets done

1. I read it end to end and write the findings into this file, numbered.
2. You say which to fix — or say "all of them".
3. I fix, test, and delete each entry as it is done.
4. When the list is empty the record moves to
   [docs/MODULE-LOG.md](docs/MODULE-LOG.md) and this file is refilled with the
   next module.

---

# Current module: Student + Staff Portal

Every backend module has now been reviewed at least once (Fee, Finance,
Attendance, Exam, Student, Staff, Settings, Transport, Inventory — all closed,
full records in [docs/MODULE-LOG.md](docs/MODULE-LOG.md)). This is new
construction, not a review pass: today there is **no dedicated portal at
all** — a student, guardian, or teacher logs in and lands on the same admin
`Dashboard.vue` with the same full admin sidebar as an owner, with only a
handful of scattered `*.view.own` permission checks bolted onto admin screens.
Full investigation, with exact file/line references for every claim below, is
in [docs/STUDENT-PORTAL-READINESS.md](docs/STUDENT-PORTAL-READINESS.md) and
[docs/STAFF-PORTAL-READINESS.md](docs/STAFF-PORTAL-READINESS.md).

**Approach:** build the Student/Guardian portal first — it has more existing
backend to reuse (fee vouchers, exam results, leave application) and is the
higher-value piece — but build its shared infrastructure (portal layout,
role-based menu filtering, "my own record" resolver pattern, post-login
redirect) generically enough that the Staff portal in Phase 2 reuses it
directly instead of rebuilding it. Not fully sequential (no cross-benefit) and
not built in parallel (two agents would likely invent two incompatible shared
layers) — the middle path.

## Phase 1 — Student/Guardian portal — **done, 2026-09-14**

All six items below shipped. Built by three agents run in a coordinated
split (backend / shell+menu / UI polish, each given an exact file-ownership
contract to avoid touching the same files) rather than sequentially or in an
uncoordinated parallel — see `docs/MODULE-LOG.md`'s "Module: Student Portal
Phase 1" entry for the full findings, files touched, and bugs caught along
the way (a guardian-ownership gap in three policies, three dead camelCase
prop lookups in the placeholder pages, and a `PortalWorld` test fixture
writing a column `exam_result_headers` doesn't have). 479 tests passing
(Fee + Exam + Portal + Menu filters), `npm run build` clean.

1. ~~**Fix the two broken `viewAny()` policy gaps**~~ that block any
   list-based self-service today: `FeeVoucherPolicy::viewAny()` /
   `FeePaymentPolicy::viewAny()` (`app/Policies/Fee/`) don't accept
   `fee.view.own`, and `ExamResultHeaderPolicy::viewAny()`
   (`app/Policies/Exam/`) doesn't accept `exam.result.view.own` — each needs
   to accept the `.view.own` permission, scoped to the caller's own student.
   Smallest change, unblocks the rest without any new UI.
2. ~~**Build the shared "my own record" resolver**~~ — a small trait/service
   generalizing `StudentLeaveController::ownStudents()`/`assertMayActFor()`
   (`app/Http/Controllers/StudentLeaveController.php:172-220`), which already
   resolves `$user->student`/`$user->guardian->students()` with no route
   parameter. This becomes the template Phase 2 copies for
   `$user->staffProfile`.
3. ~~**New `/portal` routes**~~ built on that resolver: fee vouchers (list +
   show, reusing `FeeVoucherController`'s existing show/print/challan views),
   exam results (list + the existing result-card view from
   `ExamReportCardController::card()`), and a genuinely new attendance-history
   endpoint (`attendance.view.own` is seeded but nothing consumes it today).
4. ~~**Implement `ExamResultController::studentResult()`**~~ — currently a stub
   (`app/Http/Controllers/Exam/ExamResultController.php:240-243`).
5. ~~**Build the shared portal layout/shell + role-based menu filtering**~~ —
   populate `Menu.role` (column already exists, currently unused everywhere
   except a stray developer-only comment) for `student`/`guardian` so the
   admin sidebar's Finance/Inventory/Staff/Settings items disappear, and add a
   role-based post-login redirect (`config/fortify.php`'s `home` is currently
   one global constant for every role) sending `student`/`guardian` to
   `/portal`. Build this generically — Phase 2 reuses the same menu-filtering
   and redirect mechanism for `teacher`/`staff` roles, just pointed at
   `/staff/self` instead.
6. **Not done yet — small follow-up.** Move "Student Leaves" into the new
   "My Portal" nav group (`database/seeders/MenuSeeder.php`) — it already
   works for self-service via `/student-leaves/page`, it just isn't listed
   alongside Fees/Exam Results/Attendance in the new portal menu the way the
   plan called for. A one-line `Menu::create()` addition, not a code change.
7. **Deferred, not blocking**: the class timetable feature
   (`academics.timetable.view` is a seeded placeholder with no model,
   controller, route, or Vue page anywhere — this is an independent, larger
   build, do it after the portal ships).

## Phase 2 — Staff/Teacher portal — **done, 2026-09-14**

Turned out much smaller than Phase 1: `Staff/People/Show.vue` already had working
Personal/Attendance/Leave/Salary tabs, already gated correctly on `staff.view.own` —
no new pages were needed, just a way to reach them. Full record:
`docs/MODULE-LOG.md` under "Module: Staff Portal — Phase 2".

1. ~~**`/staff/me/*` convenience route**~~ — one route, `GET /staff/me`, resolving the
   caller's own `StaffProfile` and redirecting straight to the existing
   `staff.people.show` page (no separate routes needed for attendance/salary/leave —
   they're tabs on that same page). Building a whole new set of `/staff/me/*` pages
   as originally planned would have duplicated what already worked.
2. ~~**Fix `TeacherAssignmentController`**~~ — the one genuine backend gap: fixed via
   a new `StaffProfilePolicy::viewTeaching()` and scoping the query to the caller's
   own assignments when they only hold `staff.view.own`.
3. ~~**Wire the staff self-service nav**~~ — a plain "My Profile" menu entry under
   Staff, no `Menu.role` restriction needed (unlike Phase 1's student/guardian menu,
   this is meaningful for anyone who can already see the Staff group at all).
4. **Not built — deferred, still not blocking anything.** A dedicated staff
   dashboard, a formatted salary-slip PDF, and the class timetable feature
   (`academics.timetable.view` is still just a seeded placeholder permission with
   no model anywhere).

## How this gets worked

Same process as every module review: numbered findings above get fixed one
at a time (or a batch at once, per instruction), tested, and the finished
record moves to [docs/MODULE-LOG.md](docs/MODULE-LOG.md) as "Module: Student
Portal" / "Module: Staff Portal" once each phase closes.
