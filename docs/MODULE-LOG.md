# Completed module log

A finished module's record, moved here when `CURRENT-MODULE.md` is refilled with
the next one. Append-only: what was found, what was decided, and why.

---

# Module: Attendance — **complete**

Every finding is closed and every suggestion is built.

**Reviewed:** 2026-09-08 · business logic, services, controllers, models,
migrations, relations, policy, routes

**What was read.** `AttendanceService`, `AttendanceController` (1,067 lines),
`AttendancePolicy`, the four attendance migrations, `Attendance`,
`AttendanceStudent`, `AttendanceStatus`, `AttendanceSummary`, `StudentLeave`,
`Holiday`, `routes/attendance.php`, the seeders, and the Vue pages that post to
these endpoints.

**Open:** nothing. Ready for the next module.

---

## Fixed

**S4, S6, S7, S8, S10** · 2026-09-08

With S3 and S9 done the day before, every suggestion from the review is now
built. What each one is — and, as importantly, where each one deliberately
stops:

- **S4 — Ramzan and winter timings — complete.** `attendance_timings` holds a
  clock per period of the year; the **narrowest** period wins, so a campus
  states its regular timing across the session and drops Ramzan inside it
  without having to switch the regular one off and on again around it.

  And the register now **asks**: `LateArrivalResolver` sits on all three write
  paths — new register, whole-class save, edit screen — so a check-in past the
  deadline marks the child late by itself. Deliberately narrow: it upgrades
  *present* to *late* and does nothing else. A teacher who marked late meant it
  (a child can reach the gate on time and the classroom ten minutes later), and
  absent, leave and half day are statements about the whole day that an arrival
  time cannot contradict. A campus with no clock set — every campus until one is
  configured — sees no change.

- **S6 — biometric and RFID, backend only, as you asked.**
  `attendance_devices`, `attendance_device_identities` (what the machine calls a
  person) and `attendance_device_punches`. `AttendancePunchImporter` takes a
  list of punches, matches them, and refuses to store the same punch twice —
  re-reading a machine's log is the normal way to recover from a failure.
  *Left here at your instruction.* **No device driver**, which is the point —
  every make speaks its own protocol. `dailySummary()` gives the first arrival
  and last departure of a day as a **read**; nothing writes them onto a register.
  Picking that up means choosing a machine first.

- **S7 — late arrival fine, optional.** Off by default and stays off until a
  campus turns it on. Grace count, per-late amount and a monthly cap; the charge
  is recomputed from the month's summary rather than added to, and left alone
  once billed. Attendance works it out, the fee run bills it, and
  `student_late_arrival_fines` is the whole of what passes between them.

- **S8 — monthly attendance for a report card.** `ReportCardAttendanceService`
  gives a child or a whole class "days present out of working days" for a term,
  with a test that it agrees with the class report.
  *Deferred to the exam module, at your instruction.* The figures are ready and
  tested; the result card that prints them is the exam module's, and this will
  be finished there.

- **S10 — leave application.** Corrected to your point: **there is no guardian
  portal.** A guardian signs in to the student's portal, so one endpoint serves
  the child, the family and the office, and who applied is recorded rather than
  inferred. Whoever may change a class's register may decide its leave — the two
  are the same responsibility, so they are answered in one place.
  *Deferred to the portal, at your instruction.* Backend and routes are done and
  tested; the screens come when the student portal is built.

Tests: `Policy/Case_01_TimingsTest` (10), `Policy/Case_02_LateArrivalFineTest`
(10), `Devices/Case_01_PunchImportTest` (11),
`Reports/Case_06_ReportCardAttendanceTest` (11),
`Leave/Case_01_LeaveApplicationTest` (14), `Policy/Case_03_AutoLateMarkingTest`
(13).

---

**A13 + S9, S3** · 2026-09-08

**A13 — a teacher sees their own classes and nobody else's.** Nothing in the
system recorded which class a teacher was responsible for, so the policy could
not ask the question: every method was a bare permission check that ignored the
record, and any teacher holding `attendance.view` could read **every campus's**
registers.

`teacher_class_assignments` records it — subject teacher or class teacher, per
session, with the campus coming off the staff profile where it already lived.
Exactly one class teacher per section is enforced in the database, not trusted
to a screen.

Access now has three widths, decided in one place (`coversRegister()`) so the
policy methods cannot drift apart:

| Width | Who | Sees |
|---|---|---|
| School | developer, owner, super admin | every campus |
| Campus | campus admin, head teacher | their campus, every class in it |
| Class | teacher | only the sections they are given |

A policy guards opening one record; a list needs the same question asked of
every row, so `Attendance::visibleTo()` sits beside it and the tests assert the
two agree. A teacher with no class yet sees **nothing**, which is the safer way
round to be wrong. Reports are scoped the same way, and the class picker offers
only classes the user may actually open.

Reopening a closed register is deliberately narrower than closing one: a head
teacher may sign a register off, but only a campus admin or above may open it
again. That is the point of closing it.

**S9 — registers close themselves.** `attendance_policies.lock_after_days` per
campus, and `php artisan attendance:lock-settled` (with `--dry-run`). Zero — the
default — closes nothing, so a school that has not asked for this sees no
change.

**S3 — children who have stopped coming.** `ConsecutiveAbsenceService` finds a
run of unexplained absences that is **still going**; a run that ended is not a
child drifting away, and flagging them buries the child who is. Approved leave
neither counts nor breaks a run — the family told the school, which is the
opposite of the signal being looked for.

Two older suites needed a class assignment added, which is the new rule working:
a teacher who is given no class can no longer touch a register.

Tests: `tests/Feature/Attendance/Access/Case_01_ClassScopingTest` (17),
`Case_02_AutoLockTest` (8), and
`tests/Feature/Attendance/Reports/Case_05_ConsecutiveAbsenceTest` (10).

---

**A27 — deleted** · 2026-09-08

`storeIndividual()` had no route, no caller anywhere, and — decisively — never
refreshed the summaries or recorded the absence alerts. Routing it would have
left the derived records stale behind it. What it did, adding a child who was
missed, the class register does properly: with the lock checked, the summary
rebuilt and the guardian told.

`AttendanceService::validateTimes()` and `markAllStudents()` went with it. The
first had that one caller and the form requests do the checking now; the second
never had a caller at all.

**A25 — kept, by your decision** · 2026-09-08

`student_leave_records` stays, for the history of a child leaving. The
duplication is real but only partial, and the halves are worth naming:

| | `student_enrollment_records` | `student_leave_records` |
|---|---|---|
| **when** they left | `leave_date` — the source of truth; the period model depends on it | duplicate |
| **status** at departure | `student_status_id` | duplicate |
| **why** they left | not held anywhere | `description` |

The enrollment record answers *when*, the leave record answers *why*, and
nothing currently writes the second. That belongs to the Student module's
leaving flow, which does not exist yet — carried forward rather than built here.

---

**A12** · 2026-09-08

The module could only answer "where is this child **now**". Every roster and
every report was built from the open enrollment, so opening September listed
today's roll, a child who moved from 5-A to 5-B in November appeared under 5-B
for the whole year, and a child who had since left vanished from the report
along with the months they were actually present.

The enrollment periods exist precisely so history survives this. Two scopes now
express the questions the module was failing to ask:

- `coveringDate($date)` — the period that was open on a day. Used by the roster
  and by the whole-class save, so a back-dated register is filed under the
  section the child was in **then**.
- `overlappingPeriod($from, $to)` — every period touching a span. Used by both
  reports, so a child who left mid-month is still on them, with a denominator
  that stops on the day they left rather than running to the end of the month.

`registersTouchedBy()` was reading today's sections too, which meant the lock
check on a back-dated save looked at the wrong registers.

Tests: `Case_11_HistoryTest` (11) — the move, the leaver, the back-dated
register, and both reports agreeing with each other.

---

**A8** · 2026-09-08

The last of the tables that copy the enrollment's section across while being
NOT NULL themselves — the enrollment and the fee tables were done during the fee
review, attendance was left because it belonged here. Those children could not
be marked present at all.

The unique index needed the same care it needed on the enrollment table: a
database treats each NULL as distinct, so `[date, class, section]` stops
protecting anything once the section is null and a class could quietly collect a
second register for the same day. A generated column substituting zero for NULL
gives the constraint something real to compare; SQLite says the same thing as
two partial indexes.

Two things in the controller had to follow. `registersTouchedBy()` built its
section list with `->filter()`, which **drops nulls** — so for a section-less
class the lock query looked for nothing and the lock was never checked at all.
And `where('section_id', null)` is not the question `whereNull('section_id')`
asks, so every lookup now goes through `scopedToSection()`.

Tests: `Case_10_ClassWithoutSectionsTest` (9), including the lock case.

---

**A5, A16, A22** · 2026-09-08

- **A5 — the cache.** Saving a holiday called `Cache::flush()` **three times in
  a loop**, throwing away the whole application cache with it. The holiday
  answers are cached per date and per campus, so there is no one key to forget
  and no pattern delete on the database store — the keys carry a version now,
  and `forgetHolidays()` moves it forward, which retires every existing entry at
  once and leaves everything else alone. The per-request copy of the status ids
  is cleared too; it was not, so a status added mid-request stayed invisible.
- **A16 — one query, not three.** `statusCounts()` groups once and the accessors
  read from it, and when the rows are already loaded — the dashboard, the show
  screen — nothing is queried at all. `leave_count` and `half_day_count` were
  added while there, and `total_students` no longer queries a loaded relation.
- **A22 — the student report.** The route binds the child; the method ignored
  the binding and demanded a `student_id` in the query string, so calling the
  route as named failed validation before it did anything. It takes the bound
  model now, and the month comes from `AttendanceReportRequest`, which defaults
  to the current one rather than refusing a report opened from a menu. The
  report also gained the same expected-days denominator the class report uses,
  with a test that the two agree.

Tests: `Case_09_CacheAndCountsTest` (7) and
`tests/Feature/Attendance/Reports/Case_04_StudentReportTest` (8).

---

**A14, A17, A18, A23, A24** · 2026-09-08

- **A14 — `App\Enums\AttendanceStatusCode`.** The four codes plus the half day
  were string constants on **two** models at once and compared as bare strings
  in a dozen places. One enum now, and both copies are gone.

  Deliberately **not** a cast on `attendance_statuses.code`: that column stays a
  plain string so a school can add its own status — "Short Leave" at a weight of
  0.5 — which a cast would turn into a `ValueError` the moment the row was read.
  The enum names the codes the application reasons about; the table holds every
  code the school uses, and the counting reads the weight off the row. There is
  a test for exactly this.
- **A24 — form requests.** `StoreBulkAttendanceRequest`,
  `UpdateAttendanceRequest` and `StudentsForAttendanceRequest`. The last of
  those replaces the module's only hand-built `Validator`, whose errors reached
  the screen in a different shape from every other endpoint's.
- **A17 — `section_id` is checked properly.** It must be a section that exists
  **and belongs to the class being marked**, or the zero that means the whole
  class — which is now a named constant on the request that receives it rather
  than a bare literal in three places. Any integer used to reach the insert and
  come back as "Failed to record attendance: SQLSTATE…".
- **A18 — the times are validated on the path that is used.** Check-out before
  check-in, and a check-out with no check-in, are both refused on the bulk save
  as they already were on the other two.
- **A23 — `check_in` and `check_out` are left as strings.** Cast as datetimes,
  a clock time gained an arbitrary date part and Eloquent wrote a full
  `Y-m-d H:i:s` back into a column that holds only a time. The JSON the screens
  receive is unchanged.

Two more caught while writing the rules: a status the school has **switched off**
is now refused on a new register, and the **same student twice in one
submission** is refused rather than having the second row silently overwrite the
first.

Tests: `Case_07_ValidationTest` (16) and `Case_08_StatusCodesTest` (8).

---

**A7, A9, A26 + S1, S2, S5** · 2026-09-08

Settled first, since A7 was a decision: **the summary table stays, as a derived
cache and never a source of truth.** A report card and a government return both
want "days present out of working days" per child per month, and recomputing a
year of registers for a whole school on every request is not something to do
twice. What makes that safe is that a month is always **recomputed**, never
adjusted, and `php artisan attendance:rebuild-summaries` reconstructs any of it
from `attendance_students`.

- **S5 — `attendance_policies`** per campus, optionally per session, shaped like
  `fee_policies`: which ISO weekdays the campus works, which are short days, and
  whether absences are told to guardians. A campus that has set nothing gets a
  Monday-to-Saturday week rather than no denominator at all.
- **A26 — a real denominator.** `WorkingDayCalculator` counts the days a child
  was actually expected: the campus's working weekdays, less holidays (asked
  through the same method the register guard uses, so the two cannot drift), less
  anything outside their enrollment. A child admitted on the 20th is not absent
  for the first nineteen days.
- **S2 — half day.** `attendance_statuses` gained a `weight`, and every count is
  read off the status row instead of testing the code. Half Day is seeded at 0.5;
  a school can add "Short Leave" at 0.5 and the reports follow with no code
  change. Late is weighted 1.0 — the child was in class, and the lateness is
  recorded to be chased, not to dock the attendance.
- **A7 — the summaries are written.** Rebuilt inside the same transaction as the
  register save, so a summary that disagrees with its register cannot exist.
  `expected_days`, `present_equivalent`, `half_day_count` and `computed_at` are
  new; `attendance_percentage` measures against expected days, and
  `unmarked_days` surfaces the days nobody took a register at all rather than
  letting them flatter everyone's figure.
- **A9 — `unique_monthly_summary`** is the unique constraint its name always
  claimed to be.
- **S1 — absence alerts.** `attendance_absence_alerts` records what is owed to
  which guardian, with the message as it went out — it is the answer to "nobody
  told me", so the row survives sending. One per child per day however often the
  register is saved. Only absence qualifies: a child on approved leave is
  expected to be away, and a late arrival is in school. A family with no number
  on record is marked **skipped**, not failed, because that is a contact detail
  to fill in rather than a delivery that went wrong. The gateway is
  `config/attendance.php` and defaults to the log, so the pipeline is complete
  and testable before an SMS vendor is chosen.

Also closed on the way past: **A21** — `AttendanceStatus` can set `is_active`
now, and has `active()` and `ordered()` scopes.

One design decision worth recording: a campus's **short day does not halve the
denominator**. A child who attends the whole of a short Friday has done
everything asked of them, so it counts as one expected day like any other —
halving it would let a full attender finish above 100%. The school's short day
and the child's half day are different things, and only the second is a weight.

Tests: `tests/Feature/Attendance/Reports/Case_02_WorkingDaysTest` (10),
`Case_03_MonthlySummaryTest` (14), and
`tests/Feature/Attendance/Alerts/Case_01_AbsenceAlertTest` (12).

---

**A1, A3, A4** · 2026-09-08

- **A1 — the lock now holds on the path that is actually used.** `storeBulk`
  resolves the registers a save would touch and authorises `update` on each
  before the transaction opens, so both the permission and the lock are
  enforced and a refusal is a 403 rather than a swallowed error. The whole-class
  save is covered too: if any one section's register is locked, the save is
  refused. `storeIndividual` got the same guard.
- **A3 — `unique_student_attendance` is a unique constraint now**, not an index
  wearing the name. `2026_09_08_000007_enforce_one_attendance_row_per_student.php`
  removes existing duplicates first, keeping the most recently written row for
  each student, and `upsertStudentAttendance()` was rewritten as an
  `updateOrCreate` keyed on the pair the constraint covers.
- **A4 — a register can only change its own rows.** The row ids are validated
  with `Rule::exists(...)->where('attendance_id', $attendance->id)` and fetched
  through `$attendance->attendanceStudents()`, so an id from another class is
  refused twice over.

One further bug surfaced while fixing them: **an existing register was never
found**. `attendance_date` is stored as a datetime whose time part is zero, and
every lookup compared it against a plain `Y-m-d` string, which does not match.
The saves therefore always tried to insert a second register for the same day —
invisible on MySQL, which coerces the comparison, and a constraint violation on
SQLite. All three lookups now go through `registersOn()`, which uses
`whereDate`. Without this the lock check in A1 could not find the register it
was supposed to be checking.

Also cleaned up while in there: the bare `0` meaning "all sections" is now the
named constant `AttendanceController::ALL_SECTIONS`, compared as an integer
rather than with `==`.

Tests in `tests/Feature/Attendance/Register/` — `Case_01_LockTest` (9),
`Case_02_DuplicateMarkingTest` (6), `Case_03_CrossRegisterTamperingTest` (5),
with `tests/Support/AttendanceWorld.php` as the shared setup. The lock cases act
as a **teacher**, not the developer: `Gate::before` grants developers every
ability without consulting a policy, so a developer never meets the lock at all.

**A2, A6, A10, A11** — with **A15, A19, A20** pulled in · 2026-09-08

- **A2 — leave detection runs.** `getStatusId()` now accepts a code as well as
  a name; it returned null for every code before, so the block behind it never
  executed. `markAllStudents()` had the same fault.
- **A15 — and it fires when the id is a string.** Fixing A2 alone would not have
  been enough: the id arrives from a request as `"3"`, and the comparison was
  strict. The four call sites now go through `AttendanceService::isLeaveStatus()`,
  which compares as integers.
- **A20 — `StudentLeave` was unusable.** `'status' => 'enum'` is not a Laravel
  cast; it threw `InvalidCastException` the moment the attribute was read, so
  A2's detection could not have worked even with the lookup fixed. Replaced with
  a proper `App\Enums\LeaveStatus` backed enum, and the model's scopes and
  checks now use it.
- **A6 — the locked edit screen.** The return type is `Response|RedirectResponse`.
  Note what the tests showed: a teacher is stopped by the policy with a 403 and
  never reaches the guard; the only caller that reaches it is a developer, whom
  `Gate::before` lets past the policy. That is the path that used to throw.
- **A10 — the primary guardian.** `where('is_primary', true)`, not a `type`
  column that does not exist.
- **A11 — the holiday guard.** Moved into `assertNotAHoliday()` and applied on
  all three write paths. The whole-class path had no check at all; the
  individual path computed the answer and discarded it.
- **A19 — and the screen now agrees with the save.** `checkHoliday` reported any
  holiday as a holiday, so it blocked days marked as working days that the save
  would have accepted. It answers on `isAttendanceAllowed()` now and returns
  that flag alongside.

Two further bugs surfaced while fixing them, both blocking the work:

1. **The class report returned a 500 whenever any student had no records in the
   month.** `calculateStats()` demanded an Eloquent collection and the report
   passes `collect()` — a plain one — as the empty default. Since running the
   report before a class has been marked is the ordinary case, this was close to
   permanently broken. Typed to the base collection now.
2. **Holidays were never found**, the same date comparison as before:
   `start_date <= '2026-04-06'` is false when the stored value is
   `'2026-04-06 00:00:00'`. Invisible on MySQL, which coerces; fatal on SQLite.
   `getHoliday()` uses `whereDate` now. Without it the holiday guard could not
   be verified at all.

Tests: `Case_04_LeaveDetectionTest` (10), `Case_05_HolidayGuardTest` (9),
`Case_06_LockedEditScreenTest` (4), and `tests/Feature/Attendance/Reports/Case_01_GuardianContactTest` (4).

---


## Waiting on another module

Three suggestions are built as far as this module can take them, and stop where
another begins. None is a gap in attendance:

| | Waiting on | What is already done |
|---|---|---|
| **S6** biometric / RFID | a machine being chosen | tables, importer, matching, duplicate handling — everything but the driver |
| **S8** report card attendance | the exam module | `ReportCardAttendanceService`, tested to agree with the class report |
| **S10** leave application | the student portal | tables, service, requests, controller, five routes |


## Carried forward

**The leaving flow is not built.** `student_leave_records` is kept for the
reason a child left (A25), and nothing writes it. Whoever builds "mark this
child as left" in the Student module must close the enrollment period **and**
record the reason, or the table stays empty and "why did they go" stays
unanswerable.

**From the Fee module.**

**A8 above is the attendance half of the section-less class decision.** The exam
tables were already nullable and needed nothing.

---

# Module: Exam — closed 2026-09-09

**Reviewed** 2026-09-08 · business logic, services, controllers, models,
migrations, relations, routes. **16 findings and 8 suggestions, all done.**
201 tests.

**What it was.** The schema was the best-designed part of this system — marks
snapshotted against the paper they were sat under, real unique constraints,
revaluation given its own request-and-action tables. The code on top had not
kept that promise.

## Findings

| | |
|---|---|
| **E1** | No authorisation anywhere. Every route on `auth` alone, no policy, not one `authorize()` call — any signed-in user could read and change every child's marks, publish results, lock an exam. Now three policies sharing one `ChecksExamReach` trait, `permission:` middleware on every route, and `visibleTo()` scopes so lists cannot leak what records refuse. |
| **E2** | Three routines wrote marks and disagreed. The injected service was never called. |
| **E3** | Absent was taken out of *both* sides: a child who sat one paper of eight and scored 45/50 read **90%**. Absent now scores zero; exempt stays out of both. |
| **E4** | A verified or published result dropped back to draft on any edit. |
| **E5, E12** | Bulk registration asked for `status` on a table with no `status` column — it had never once worked. Now reads the roll **as it stood when the exam was sat**. |
| **E6, E7** | Marks were unbounded (150 out of 100 accepted) and a paper need not belong to the exam. |
| **E8, E9** | The grading scale ignored `campus_id` and `session_id`. `GradeResolver`: campus, then session, narrowest wins. Overlapping bands refused at save. |
| **E10, E11** | `publish()` set a timestamp the status disagreed with and published an empty exam silently; `lock()` never wrote `locked_by`. |
| **E13** | Nothing checked a child was registered. Registration is now implicit **and recorded**. |
| **E14** | A phantom `student_id` write. |
| **E15** | A bulk save was one transaction and one header rebuild per child. |
| **E16** | Revaluation wrote four columns the table does not have; approve/reject/apply returned English and changed nothing. |

## Suggestions

| | |
|---|---|
| **S1** | Position in class — both widths, ties 1/2/2/4, unfinished results unranked. |
| **S2, S4** | The result card, printable, one to a page — with the attendance line that attendance **S8** had been holding. |
| **S3** | Grace marks, recorded **as grace** with a reason and an actor, never merged into the obtained marks. |
| **S5** | `exams.result_weight` and `AnnualResultService` — the terms weighted into a year. |
| **S6** | Pass and fail: per subject, plus the school's aggregate where set. Promotion left to the Student module. |
| **S7** | `SubjectRole` — core, elective, additional. |
| **S8** | The datesheet, grouped by day, cancelled papers struck through not dropped. |

**Left to the next module:** promotion to the next class (Student), and the Vue
screens for grace, subject roles, the card and the datesheet.

---

# Module: Staff — **complete**

Built and rebuilt across eight phases rather than found broken and fixed — the
one module finished this way instead of the read-end-to-end process every
other row in this log went through.

**Worked on:** 2026-09-09 to 2026-09-13 · schema, models, services, policies,
controllers, routes, seeders, every screen. **89 tests, PHPStan 0.**

**What it was.** Not a stub: departments, designations, staff records, payroll
generation and the Finance journal all existed and ran — one 354-line
controller, four tables, five models, eleven routes, ten seeded permissions of
which **not one was used**. `designation_id` on the profile was singular, so a
person doing two jobs (the man who drives the van and does the gardening) had
no way to be one employee. Staff attendance did not exist at all — `attendances`
is the *student* register. `teacher_class_assignments` existed and drove the
class width in Attendance, Exam and Student, and held **zero rows**, because
nothing had ever written to it.

## Phase 0 — the one decision

One salary per person, not one per job. A second job pays through a salary-head
allowance, never a second salary record.

## Phase 1 — Foundation

`staff_assignments` (one person, many jobs, generated-column-enforced single
primary), `salary_heads` + `staff_salary_components` (named pay, effective-dated,
a raise is a new row), `staff_qualifications`, `staff_documents`,
`staff_attendances`, `staff_leave_types` + `staff_leaves`,
`staff_employment_periods` (the same enrolment-period shape students use). CNIC,
phone, dob and emergency contact added to `staff_profiles`. Old lump columns
(`basic_salary`, `allowance_amount`, `deduction_amount`) kept as a fallback, not
dropped.

## Phase 2 — Who sees what

`StaffProfilePolicy` on the shared `ChecksSchoolReach` trait — the same three
widths as Attendance, Exam and Student. **Salary is its own width**:
`staff.salary.manage` is separate from `staff.manage`, so a campus admin who may
hire and edit still may not see what anybody is paid. `staff.view.own` is the
staff portal.

## Phase 3 + 4 — The person, their jobs, and the teacher

`StaffAssignmentService` (give/end/make-primary a job), `StaffEmploymentService`
(join/leave/rejoin), `TeacherAssignmentService` — the service that finally
writes to `teacher_class_assignments`, closing the gap that had left Attendance,
Exam and Student's class-width rule correct but starved of data.

## Phase 5 — Staff attendance and leave

`StaffAttendanceService` and `StaffLeaveService`. Reused rather than rebuilt:
`LateArrivalResolver` and `WorkingDayCalculator`, the same services the student
register already used correctly — a second copy of either would have drifted
from the first. Leave carries `is_paid` on its type, which is what a payroll run
reads. Two bugs caught before they shipped: a date-cast column compared with
`whereBetween` against a plain string silently failed to match on SQLite (the
same "bug 19" class already logged against Attendance); `updateOrCreate` against
a date-cast column had the same problem, fixed with an explicit find-then-fill.

## Phase 6 — Salary and payroll

`StaffSalaryService`: named allowances and deductions, falling back to the
legacy lump columns when a profile has no components at all, so nothing running
broke while the shape changed underneath it. `StaffPayrollService` replaced the
payroll logic that used to sit inline in `StaffController`, added an unpaid-leave
deduction priced at the working-day rate, and called the existing
`UnifiedAccountingService` journals **unmodified**. One real bug: `PayrollRunItem`
soft-deletes, and the old `->delete()` before regenerating a run left rows behind
that blocked the unique `(payroll_run_id, staff_profile_id)` index on the next
attempt — fixed to `->forceDelete()`.

## Phase 7 — The dashboard, and the old screen goes

`Staff/Index.vue` — one page holding the staff list, payroll, departments and
designations — is deleted. Its jobs moved to `Staff/Dashboard.vue`,
`Staff/People/{Index,Show}.vue`, `Staff/Teaching/Index.vue` and
`Staff/Payroll/Index.vue`; departments/designations became a dialog on the
directory rather than a Settings screen, since no Settings module exists yet.
**A second real bug, found while building the screens that would have exposed
it:** `StaffProfileController::list()` and `::show()` never masked
`basic_salary`/`allowance_amount`/`deduction_amount` for a viewer without
`viewSalary` — only the old dashboard controller had ever done that masking, and
until Phase 7 it was the only place staff were listed. Fixed in both methods.

**Not built — no business rule given:** salary advances/loans against future
pay, a printed payslip. Raise either with the user if a school asks.

**Left to nobody in particular:** Settings doesn't exist yet, so departments and
designations stayed a dialog rather than moving there — revisit if a Settings
module is ever built.

# Module: Fee & Finance — closed 2026-09-13

## Findings

- **FF1** (fixed) — several Fee/Finance endpoints validated inline instead of
  through a Form Request; extracted 9 new Form Request classes.
- **FF2** (fixed) — `FeePaymentController::store()` read `$validated['vouchers']`
  where the request actually sent `charges`.
- **FF11** (fixed) — an enum instance used as an array key threw a `TypeError`,
  uncaught because the surrounding `catch` only caught `\Exception`.
- **FF12** (fixed) — `by-student` routes bound `{student}` in the URL but the
  controllers read `student_id` from the query string; the endpoint never
  worked from its own declared shape. Fixed via route-model binding in both
  `FeeVoucherController::getByStudent()` and `FeePaymentController::getByStudent()`.
- **FF13** (fixed, critical) — `fee.view.own` was never checked by
  `FeePaymentPolicy`/`FeeVoucherPolicy`; would have locked student/guardian
  portal accounts out of their own fee records. Fixed with `isTheirOwn()` +
  `viewByStudent()`, matching `ExamResultHeaderPolicy`'s existing pattern.
- **FF7 — architecture decision (implemented)**: Fee payments used to write to
  both the double-entry books (`UnifiedAccountingService`) and the legacy
  `Ledger` table. `TransactionController::index()` already merges both into one
  feed, revealing the codebase's real intent: `Ledger` for genuinely manual
  finance-only entries, the double-entry books as the system of record for
  anything a module (Fee, Purchases, ...) generates. Fee payments no longer
  write a `Ledger` row at all; `FinanceController::index()`'s cash summary now
  reads `UnifiedAccountingService::cashMovementTotals()` (debits/credits to
  Chart-of-Account codes `1000`/`1010`) merged with the legacy ledger totals,
  so the dashboard stays accurate for both sources.
- **FIN1** (fixed, **critical**) — `ReceivePaymentController::store()`'s
  student-payment path bypassed the Fee module entirely: no `FeePayment`, no
  `FeePaymentAllocation`, no journal — just a bare `fee_vouchers.paid_amount`
  mutation. A payment taken through this screen was invisible to the Fee
  module's own payment list, produced no receipt, and could not be reversed.
  Fixed by extracting the shared logic into `App\Services\Fee\FeePaymentService`
  and having this controller call it, same as `FeePaymentController` does.
- **FIN2** (fixed, **critical**) — the same handler queried
  `LedgerCategory::where('code', 'TUITION_FEE')` against a column
  `ledger_categories` has never had — guaranteed SQL error on every call, so
  the path could never actually run. Removed with the `FeePaymentService` fix.
- **FIN3** (fixed, **high**) — `MakePaymentController::store()` required
  `purchase_id` unconditionally, so 8 of the 10 seeded expense categories
  (Salary, Rent, Electricity, Internet, Transport, Maintenance, Other) had
  nowhere to attach and could never actually be recorded despite being offered
  in the category dropdown. Fixed by branching on whether `purchase_id` is
  present, mirroring the "student" vs "other" branch `ReceivePaymentController`
  already uses.

## Other fixes made along the way

- Orphaned Vue pages deleted (`Exam/Marking/MarkSheet.vue`,
  `Fee/Vouchers/{Print,PrintBatch}.vue`) and two unreferenced favicon files and
  a starter-kit leftover component (`PlaceholderPattern.vue`) removed.
- **Inventory module** (found incidentally, out of this session's original
  scope): `InventoryTypesController::index()` rendered
  `inventory/InventoryTypes/Index`, a path with no Vue file behind it at all —
  the real page lives at `inventory/Types/Index.vue`. Fixed the render call.
  Not yet covered by a dedicated test; Inventory itself has not been reviewed.

## Tests

`tests/Feature/Fee/**`, `tests/Feature/Finance/**` — 196 passed, 365 assertions.
PHPStan baseline unchanged (0).

## Not done — flagged, not fixed (out of scope this session)

- `ArtisanCommandController`/`CacheController` expose unrestricted artisan
  command execution (including `migrate:fresh`) from a web UI with no
  visible extra guard beyond normal route middleware — worth a dedicated
  security pass.
- Inventory module has not been reviewed end-to-end; only the one broken
  route above was caught.

# Module: Settings — closed 2026-09-13

16 controllers under `app/Http/Controllers/Settings/` (plus `ExamSettingsController`,
`FeeSettingsController` reviewed separately in their own modules). Read end to end
across 3 parallel passes (school/org structure, academic/lookup, user/system).

## Findings

- **Menu management was unusable for anyone but `developer`** (critical) —
  `MenuController` authorized against `settings.manage`, a permission never
  seeded; the real one is `school.menu.manage`. Also fixed: a frontend
  permission check that hid the "Add Menu" button for everyone, edit/delete
  buttons that rendered regardless of permission, a hardcoded
  developer-only lockout on menu *creation* only (inconsistent with edit/delete),
  a missing cycle guard on `parent_id` reparenting (a crafted A→B→A chain could
  hang `buildMenuLabel()`'s loop forever), orphaned children left behind when a
  parent menu is deleted, and an `Undefined array key "parent_id"` crash on
  create/update when the field is omitted (the normal case for a top-level menu).
- **Campus, CampusType, SchoolClass, Section, Subject controllers had zero
  authorization** (critical) — any signed-in account, including student/guardian,
  could create/edit/delete the school's own structure. Added policies for each.
- **Section-name uniqueness was checked globally, not per class** (high) —
  "Section A" could only exist in one class school-wide, blocking completely
  normal setup (every class needing an A/B/C section). Fixed to scope by `class_id`.
- **`SchoolController`/`ClassSubjectController` gated on a nonexistent
  `settings.manage` permission** (high) — locked out `owner`/`campus_admin`,
  the roles the real seeded permissions (`school.profile.manage`,
  `academics.class.manage`) were meant for. Fixed.
- **`ExamTypeController`'s mutating actions all redirected to a route name that
  doesn't exist** (critical) — every save 500'd with `RouteNotFoundException`
  after doing its work. Fixed all 9 redirects.
- **`ExamType` model's delete path assumed soft-delete semantics it never had**
  (critical) — the only delete path the UI reaches crashed with
  `BadMethodCallException`. Added `deleted_at` directly into the create
  migration (not a new alter file, keeping the 1-table-1-migration rule intact)
  and the `SoftDeletes` trait; guarded the FK-restrict case with a friendly message.
- **`AttendanceSettingsController` gated on the same nonexistent
  `settings.manage` permission** (critical) — 403'd every non-developer account,
  including `campus_admin` holding the entire `attendance.*` wildcard. Fixed to
  `attendance.settings`, the permission actually seeded for this screen.
- **Academic sessions could end up with two active at once, or zero** (critical)
  — `store()`/`update()` didn't deactivate other sessions the way `activate()`
  did, and nothing blocked deactivating/deleting the sole active session. Every
  module that resolves "the current session" (Exam, Fee, Staff, Student) depends
  on this being unique. Fixed both gaps.
- **`SessionController`/`ExamTypeController` had zero authorization** (high) —
  fixed using the permissions already seeded for exactly this purpose.

## Not fixed — needs a product decision

- `CampusTypeController@create`/`@edit` are routed but empty no-op stubs (dead,
  unreached by the actual UI, which uses a modal instead).
- Several Inertia page routes (`campuses.*`, `school-classes.*`, `sections.*`,
  `subjects.*`, `settings/Sessions/*`, `settings/ExamTypes/*`,
  `settings/Menus/{Create,Edit,Show}`) render Vue pages that don't exist — all
  dead routes never reached by the real UI, which does this CRUD entirely
  through modals/tables backed by JSON endpoints. Logged for awareness; removing
  the dead routes or building the pages is a call for the team, not a bug fix.
- `MonthController` and `ThemeSettingsController::calculateContrast()` — minor,
  non-security, left as noted in the full agent reports.

## Tests

`tests/Feature/Settings/**` (5 new test files) — 38 passed on their own; full
regression sweep (Settings + Exam + Attendance + Fee + Finance + Student +
Staff + Inventory) — 1080 passed, 0 failed, project-wide.

# Module: Transport — closed 2026-09-14

One controller (`TransportController`), five models (`TransportVehicle`,
`TransportRoute`, `TransportStop`, `TransportStudentAssignment`,
`TransportVehicleExpense`), one route file (`routes/transport.php`) and one
Inertia page (`Transport/Index.vue`) covering vehicles, routes, stops, student
assignments, vehicle expenses and monthly dues generation. Read end to end.

## Findings

- **Zero authorization on every transport route** (critical) — all eleven
  routes sat on `auth` alone. The six `transport.*` permissions
  (`transport.view`, `transport.view.own`, `transport.vehicle.manage`,
  `transport.route.manage`, `transport.assignment.manage`,
  `transport.expense.manage`) had been seeded since the beginning and not one
  was checked anywhere, so any signed-in account — a student's own portal
  login included, since students and staff share one `User` model and guard —
  could create vehicles, reassign routes, read every campus's fleet and
  student assignments, or run payroll-adjacent dues generation. Fixed: added
  `permission:` middleware to every route (matching the Fee/Staff pattern),
  plus a `TransportVehiclePolicy`, `TransportRoutePolicy`, `TransportStopPolicy`,
  `TransportStudentAssignmentPolicy` and `TransportVehicleExpensePolicy`
  (`app/Policies/`), each checked with `Gate::authorize()` in the controller
  for the object-level campus check on top of the route-level permission gate.
- **Every list on the Transport dashboard leaked across campuses** (critical)
  — `TransportController::index()` loaded every vehicle, route, stop,
  assignment and expense in the school, and the first 200 students
  school-wide, with no campus filter at all; a campus admin at one campus
  could see and edit another campus's fleet, routes, stops, assignments and
  expenses. Fixed: added a `scopeVisibleTo(?User $user)` to each of the five
  models (same shape as `StaffProfile::scopeVisibleTo()` — a record with no
  campus is a school-wide asset and stays visible to everyone; a record with
  a campus is visible only to that campus and to super admins), applied in
  `index()` and in `generateDues()`.
- **A transport assignment's stop was never checked against its route**
  (high) — `storeAssignment`/`updateAssignment` validated `transport_stop_id`
  only against `exists:transport_stops,id`, so a student could be pointed at
  a stop that was never added to the route they were assigned to, silently
  breaking the pickup/drop list the route screen shows. Fixed: added
  `TransportController::assertAssignmentIntegrity()`, called from both
  actions, which 422s with a `transport_stop_id` validation error when the
  stop isn't one of the route's stops.
- **A transport assignment's route was never checked against the student's
  campus** (high) — same two actions validated `transport_route_id` only
  against `exists:transport_routes,id`, so a student enrolled at one campus
  could be assigned to a route belonging to a different campus. Fixed in the
  same helper: 422s with a `transport_route_id` validation error when the
  route has a campus and it doesn't match the student's enrolled campus (a
  school-wide route with no campus is still allowed for any student).
- **Vehicle double-booking** — checked, not a bug: `transport_routes` carries
  no time-of-day field, so a vehicle serving two routes (a morning run and an
  afternoon run) is the normal case, not a conflict. No schedule data exists
  to determine actual overlap; flagged here rather than "fixed" since adding
  time-of-day fields to routes is a product decision, not a bug fix.
- **Backend-without-screen** — none found. Every controller action (vehicles,
  routes, stops, assignments, expenses, generate-dues) has a corresponding
  tab and form on `Transport/Index.vue`.
- **Migration hygiene** — clean. All six transport tables
  (`transport_vehicles`, `transport_routes`, `transport_stops`,
  `transport_route_stops`, `transport_student_assignments`,
  `transport_vehicle_expenses`) are created in the one
  `2026_05_09_000001_create_staff_and_transport_module_tables.php` migration
  with every column they have today; no later alter migration to merge.

## Not fixed — needs a product decision

- Whether routes should carry a schedule (time-of-day / shift) to make actual
  vehicle double-booking checkable — today "double-booking" isn't a
  well-defined state.

## Tests

`tests/Feature/Transport/Case_01_WhoMaySeeTransportTest.php` (new, with
`tests/Support/TransportWorld.php`) — 16 passed, covering the permission
fence, campus scoping (`TransportVehicle::visibleTo()`,
`TransportStudentAssignment::visibleTo()`), and both assignment
data-integrity checks. `php artisan test --compact --filter=Transport` — 16
passed, 0 failed.

# Module: Inventory — closed 2026-09-14

10 controllers under `app/Http/Controllers/Inventory/` (Types, Items, Stocks,
Suppliers, Adjustments, Purchases, PurchaseReturns, StudentInventories,
InventoryReturns, and the consolidated `InventoryPageController`), their
models, requests and `routes/inventory.php`. Had zero test coverage and had
only ever had incidental fixes (a couple of broken Vue render paths, some
missing Show pages and the reserve/release buttons). Read end to end.

## Findings

1. **Zero authorization on every inventory route** (critical) — all routes sat
   on `auth`/`verified` alone. The ten `inventory.*` permissions
   (`inventory.view`, `inventory.item.manage`, `inventory.stock.manage`,
   `inventory.purchase.view`, `inventory.purchase.manage`,
   `inventory.purchase.delete`, `inventory.supplier.manage`,
   `inventory.return.manage`, `inventory.student.issue`, `inventory.reports`)
   had been seeded since the beginning (`PermissionsSeeder`) and not one was
   checked anywhere, so any signed-in, verified account — regardless of role —
   could adjust stock, delete a purchase, create/delete suppliers, or issue
   items to any student. Fixed: added `permission:` middleware to every route
   in `routes/inventory.php` (same shape as Fee/Transport), and added
   `AdmissionWorld::grantInventoryAbilities()` so the shared test actor holds
   them the way it already holds `fee.*`/`staff.*`.
2. **`campus_id` trusted blindly from the query string everywhere** (critical)
   — all ten controllers (82 call sites) read `campus_id` off the request and
   used it, or silently defaulted to the first campus if it was missing; a
   campus-restricted user (anyone without a school-wide role) could pass any
   other campus's id and list, reserve, or mutate its stock, purchases,
   suppliers and student issuances. `InventoryPageController`'s four dashboard
   pages (`settings`, `itemsStock`, `purchasesManage`, `studentManage`) didn't
   even accept a campus filter — every campus's rows, always. Fixed: added
   `App\Http\Controllers\Concerns\ScopesCampusForUser::resolveCampusId()` (a
   campus-restricted user's own campus, read off their staff record, always
   wins over whatever the request asked for; a school-wide user keeps
   filtering by any campus, including none) and wired it into the primary
   campus-resolution site of every controller plus all four dashboard
   queries.
3. **`InventoryStocksController::reserve()`/`release()` ignored the model's
   own success/failure result** (high) — `InventoryStock::reserveStock()`
   returns `false` when there isn't enough available stock, and the
   controller never checked it, so over-reserving beyond what's on the shelf
   silently did nothing to the row while the response still said
   `{"success": true, "message": "Stock reserved successfully."}`. Fixed: the
   boolean is checked and a `422` with `success: false` is returned when
   nothing could be reserved/released; both actions now lock the stock row
   (`lockForUpdate()`) for the duration of the mutation.
4. **A purchase return could silently return more than was in stock** (high)
   — `PurchaseReturnsController::store()`/`update()` did
   `max(0, $stock->quantity - $quantity)`, so a return for more than the
   current stock clamped to zero instead of failing, while the return record
   still logged the full requested quantity and amount — the audit trail and
   the shelf disagreed. Fixed: both methods now check current stock before
   decrementing and abort the transaction with a clear message when the
   return exceeds it; added `lockForUpdate()`.
5. **A "set"/"subtract" stock adjustment could push `available_quantity`
   negative** (medium) — `available_quantity` is a stored, unclamped
   `quantity - reserved_quantity` generated column;
   `InventoryAdjustmentsController::store()` could set `quantity` below
   `reserved_quantity` with nothing to stop it. Fixed: `reserved_quantity` is
   now clamped down with the new quantity, the same guard
   `InventoryStock::removeQuantity()` already applied; added
   `lockForUpdate()`.
6. **No row locking on the purchase/purchase-return stock mutations** (medium)
   — `PurchasesController::store()`/`update()` and
   `PurchaseReturnsController::store()`/`update()` read a stock row, did
   arithmetic in PHP, then saved, with no lock — two concurrent purchases for
   the same item could lose one's update. `StudentInventoriesController::assign()`
   already used `lockForUpdate()`; the others didn't. Fixed: `lockForUpdate()`
   added on every stock read-then-mutate path in those methods.

## Not fixed — needs a product decision

- Single-record `show`/`edit`/`update`/`destroy` endpoints across all ten
  controllers still scope to a *second*, independent `campus_id` read
  (`->when($request->get('campus_id'), ...)`) rather than the resolved one —
  for a school-wide user this is unchanged prior behaviour, but a
  campus-restricted user who already knows another campus's record id and
  simply omits `campus_id` from the request can still reach it by id via
  route-model binding. Closing this fully needs per-model policies in the
  shape of `StudentPolicy`/`ChecksSchoolReach` (a `viewAny`/`view`/`update`
  ability plus a `visibleTo()` scope for every one of the eight
  campus-scoped models here), which is a larger follow-up than this pass's
  per-endpoint `resolveCampusId()` fix.
- `2026_05_08_000003_add_financial_links_to_student_inventory_returns.php` is
  a later alter migration on a table created in
  `2026_03_01_000000_create_student_inventory_returns_tables.php` — reviewed
  against the project's 1-table-1-migration rule, but not a violation of it:
  the alter adds a foreign key to `student_account_adjustments`, a table that
  doesn't exist until `2026_05_08_000001`, two months after the original
  create. Folding it backward would break migration order on a fresh
  install, so it's left as two files.
- Concurrent `InventoryStock::firstOrCreate()` calls for a brand-new
  item/campus pair (the very first purchase/return/adjustment ever made for
  that pair) can still race between two requests; the unique constraint on
  `(campus_id, inventory_item_id)` stops silent duplication (the loser gets a
  DB error rather than a second row) but there's no retry. A much smaller
  window than the findings above, and left as noted rather than fixed.
- No Vue-level audit beyond what a prior pass already fixed (Returns/Show,
  Purchases/Show, the Stocks reserve/release buttons).

## Tests

`tests/Feature/Inventory/` (7 new files, with `tests/Support/InventoryWorld.php`
and `AdmissionWorld::grantInventoryAbilities()`) — 33 passed, covering: a
purchase raising stock and rejecting an out-of-campus item (`Case_01`); a
purchase return reversing stock and refusing to return more than is on hand
(`Case_02`); assigning inventory to a student deducting available stock,
refusing an over-assignment, and a return restoring it (`Case_03`); reserve/
release enforcing the available-quantity limit (`Case_04`); a stock
adjustment's add/subtract/set paths, including the `reserved_quantity` clamp
(`Case_05`); the permission fence across seven distinct routes plus one
allowed-through case (`Case_06`); and campus scoping — a restricted user
never sees another campus's stock even when asked for it, a school-wide user
still can (`Case_07`). `php artisan test --compact tests/Feature/Inventory` —
33 passed, 0 failed.
