# Current module worklist

Working file. One module at a time: findings go in, fixed items come out. When
the list is empty the module is done, its record moves to
[docs/MODULE-LOG.md](docs/MODULE-LOG.md), and this file is refilled with the next
one.

**How it is used**
- You say which items to fix (by id, e.g. "T1, T2").
- I fix them, add tests, and **delete that entry from this file**.
- Anything still listed below is still outstanding.
- Rules for how the work is done live in [docs/WORKING-RULES.md](docs/WORKING-RULES.md).

---

# Module: Student

**Reviewed:** 2026-09-09 · business logic, services, repository, controllers,
policy, models, migrations, relations, routes, requests

**What was read.** `StudentController` (404 lines), `StudentService` (510),
`StudentRepository` (**1,046** — the largest single file in this system),
`StudentUserService`, `GuardianService`, `StudentPolicy`, the `Student`,
`StudentEnrollmentRecord`, `Guardian` and `StudentLeaveRecord` models,
`StoreStudentRequest` (619) and `UpdateStudentRequest` (810), `routes/students.php`,
the migrations, and the 178 admission tests — which **all pass**.

**State of the module.** This is the oldest part of the system and it shows both
ways. The admission path is the best-tested thing here: 178 tests across fifteen
cases, covering guardians, fee modes, enrolment history and identity documents.
Nothing below touches that path, and it should stay untouched.

Everything **after** admission is where it falls apart. A child can be admitted
properly and then cannot be moved up a class, cannot be marked as having left
with an honest date, cannot log in to the portal that was created for them, and
is visible to every member of staff in every campus.

Three of the findings below are faults we have already fixed twice in this
codebase. Two are the carried-forward items you asked about.

**Open:** 14 findings · 6 suggestions

---

## Critical

### T1 — The policy ignores the record entirely
**Where:** `app/Policies/StudentPolicy.php`

Every method is a bare permission check. `viewAny`, `view`, `update`,
`changeStatus`, `readmit`, `delete`, `export` — not one of them looks at **which
child**.

```php
public function update(User $user, Student $student): bool
{
    if ($user->can('students.edit')) {
        return true;              // any child, any class, any campus
    }
    ...
}
```

So a teacher at the City campus edits, re-admits and marks as left any child at
any other campus. `export` hands them the whole school.

This is the third time: attendance had it, exam had it, and both were fixed with
school / campus / class widths decided in one place. `ChecksExamReach` is
already written and is the shape this needs.

There is also **no `visibleTo()` scope** on `Student`, so even with the policy
fixed the list screen would still show everybody — the lesson recorded after the
exam module.

**Fix:** campus and class widths in the policy, and the matching query scope, with
a test that asserts the two agree.

---

### T2 — A student account is created that nobody can ever log into
**Where:** `StudentUserService::createStudentUser()`, and the same in
`GuardianService::createGuardianUser()`

```php
$password = $this->generateSecurePassword();

$user = User::create([... 'password' => bcrypt($password) ...]);

return $user;      // the plaintext is dropped here, and nowhere else holds it
```

Every admission creates a login. The password is generated, hashed into the row,
and **thrown away**. It is never shown on screen, never printed on the admission
slip, never emailed, and never stored.

The email does not save it either: where the family gave no address, the account
gets a school-generated one (`SchoolEmailService`) at a domain nobody receives
mail at, so "forgot password" cannot reach them.

The student portal you are planning has, today, no user who can sign in.

`Case_08_StudentLoginTest` passes because it checks the **email**. Nothing checks
that anybody can log in — the "assertions that hide failures" lesson again.

**Fix:** decide how the family gets their credentials — printed on the admission
slip is the normal answer here — and return the plaintext once, at creation, to
whatever shows it. Then a test that actually signs in.

---

### T3 — `rand()` for a password
**Where:** `StudentUserService::generateSecurePassword()`, and the identical copy
in `GuardianService`

```php
$password .= chr(rand(97, 122));
$password .= $characters[rand(0, strlen($characters) - 1)];
```

`rand()` is not a cryptographic generator; its output is predictable from a small
amount of observed data. These passwords open a child's record.

Two copies of the same method, in two services, which is the second half of the
problem.

**Fix:** `Str::password()`, in one place, used by both.

---

### T4 — Marking a child as left always writes today's date
**Where:** `StudentRepository::handleLeave()`

```php
$currentEnrollment->update([
    'leave_date' => now()->toDateString(),
    ...
]);
```

There is no leave date on the form at all. A child who left in June, entered in
the register in September, is recorded as leaving in **September**.

That is not a cosmetic error. `leave_date` is what every historical question in
this system reads: exam registration reads it through `overlappingPeriod()`, the
fee run reads it to decide who is billed, attendance reads it for expected days.
Three months of vouchers and registers are silently attributed to a child who
had gone.

**Fix:** ask for the date, default it to today, and refuse one before the
admission date.

---

### T5 — Leaving a school does not record why
**Where:** `StudentRepository::handleLeave()` — *this is the carried-forward item*

`student_leave_records` exists, has a model, has a relation on `Student`, and
**nothing has ever written a row to it**. The table is for "when did they go and
why" — TC taken, moved city, fees.

Half of it does happen: the reason goes onto `enrollment.description`. So the
information is being captured and then put in the wrong place, and the table
built for it stays empty.

**Fix:** decide which of the two is the record, and write only that one. My
recommendation: the leave record is the register a school keeps and should be
written; `description` on the enrollment stays as the free note about that
particular period.

---

### T6 — Nothing stops a child having two open enrolments
**Where:** `StudentRepository::readmit()` and `handleReactivation()`; the
`student_enrollment_records` table

Both create a new row with `leave_date => null` **without checking whether the
previous one is still open**. Re-admit a child nobody marked as left and they now
sit in two classes at once.

`Student::currentEnrollment()` is `hasOne(...)->whereNull('leave_date')` with no
ordering, so which class they are in becomes whichever row the database returns
first — and the fee run, the register and the exam roll can each get a different
answer within the same request.

There is no database guarantee either. "One open period per child" is exactly the
partial-uniqueness problem we solved on the fee tables with a generated column
and a unique index.

**Fix:** close the open period before opening a new one, and let the database
hold the rule rather than the code remembering to.

---

## High

### T7 — Two routines re-admit a child, and they disagree
**Where:** `StudentRepository::handleReactivation()` and
`StudentRepository::readmit()`

The same job, written twice:

| | `handleReactivation` | `readmit` |
|---|---|---|
| class / section | falls back to the last enrolment | required, no fallback |
| missing them | throws `RuntimeException` | writes nulls |
| admission date | always today | takes `admission_date` |
| status | `$data['status_id']` or *Active* | Active, else **Left**, else `2` |

That last cell is not a typo in this table. `readmit()` reads:

```php
$activeStatusId = StudentStatus::where('name', 'Active')->first()?->id
    ?? StudentStatus::where('name', 'Left')->first()?->id
    ?? 2;
```

A school that renamed its "Active" status re-admits children **as Left**, and if
neither name is found the code writes a hard-coded row id.

The same shape as the three marking routines in the exam module.

**Fix:** one routine.

---

### T8 — Deleting a child leaves them enrolled
**Where:** `StudentService::delete()`

```php
$deleted = (bool) $student->delete();     // soft delete, and that is all
```

The enrolment period stays open. The child is gone from the student list and
still on the class roll — still billed by the fee run, still expected in the
register, still registered for the exam.

**Fix:** deleting has to close the period, or the two have to be the same act.

---

### T9 — Export and import are a message and nothing else
**Where:** `StudentService::export()` and `import()`

```php
public function export(Request $request): JsonResponse
{
    Log::info(...);

    return response()->json([
        'success' => true,
        'message' => 'Export started. You will be notified when ready.',
    ]);
}
```

Nothing is exported. Nothing is imported. Nothing is queued. Nobody is notified.
The routes exist, the permissions are seeded, the buttons are on the screen, and
the user is told it worked.

The same shape as the revaluation stubs in the exam module — and worse, because
`import` is how a school onboards, and it silently does nothing with the file
they spent a week preparing.

**Fix:** build them, or take the routes and buttons away until they are built.
Telling somebody their import succeeded is the part that cannot stay.

---

### T10 — Nothing validates the columns the database calls unique
**Where:** `StoreStudentRequest`, `UpdateStudentRequest`

`students` has unique indexes on `registration_no`, `student_code`,
`admission_no` and `b_form`. Neither request contains a single `unique:` rule —
156 rules between them, and not one of them is that.

So a second child on the same **B-Form number** — the identity document, the one
a data-entry clerk mistypes most — reaches the database and comes back a 500. The
clerk is shown an error page, not "this B-Form is already registered to Ahmed
Ali".

**Fix:** the four rules, and the message that names the child it clashes with.

---

### T11 — Three number generators, all of which race
**Where:** `StudentRepository::generateAdmissionNumber()`,
`generateStudentCode()`, `generateRegistrationNumber()`

```php
$lastStudent = Student::orderBy('id', 'desc')->first();
$nextNumber = $lastStudent ? (int) substr($lastStudent->admission_no, 4) + 1 : 1;
```

Read-then-write, with nothing holding the gap. Two clerks admitting at the same
moment get the same number; the unique index throws on the second and that
admission is lost with a 500.

Two more faults in the same three methods:

- `generateStudentCode()` takes `max('student_code')` — a **string** maximum.
  It is correct only while every code has identical padding, and quietly wrong
  after that.
- `generateAdmissionNumber()` does `substr($x, 4)` on whatever the last row holds.
  A school that typed its own admission number in any other format restarts the
  sequence at 1.

**Fix:** a counter the database owns, or a locked read. And read the highest
*number*, not the newest row or the largest string.

---

### T12 — Soft deletes and unique indexes cannot both be right
**Where:** `students` table, `SoftDeletes` on the model

`admission_no`, `student_code`, `b_form` and `registration_no` are `unique()`
and the rows are never really deleted. A child deleted by mistake holds their
admission number for ever: re-admitting them under it is impossible, and so is
giving it to anybody else.

Same problem, same solution as the fee tables: uniqueness that only applies to
live rows.

---

## Medium

### T13 — The policy writes a log line on every check
**Where:** `StudentPolicy::logAuthorization()`

Every method calls it, including `viewAny` on each list load. An `INFO` line per
authorisation check, several per request, for checks that **passed**.

An audit trail of who *changed* a child's record is worth having. A line saying
somebody looked at a list is noise that buries it.

`StudentService` does the same thing a second time — two `Log::info` calls around
each write, saying "creating" and "created successfully".

### T14 — `$student->class` costs a query, per student
**Where:** `Student::getClassAttribute()`, `getSectionAttribute()`,
`getNameAttribute()`

```php
$enrollment = $this->enrollmentRecords()->active()->with('class')->first();
```

An accessor that queries. Reading `class` and `section` down a list of fifty
children is a hundred queries, and it cannot be eager-loaded away because it is
not a relation.

They are not in `$appends`, so this only bites where a screen reads them — which
the list screen does.

---

## Suggestions — Pakistan

### S1 — Promotion to the next class
*This is the carried-forward item.* `students.promote` is seeded and nothing uses
it.

A school promotes a **whole section at a time** at the end of the year, not a
child at a time. Three outcomes per child, all normal here: **promoted**,
**detained** (repeats the class), and **promoted with condition** (failed one
subject, moved up anyway).

The exam module now answers the question this needs —
`AnnualResultService::forStudent()` gives the year's result and whether it was a
pass — so promotion is: close this year's period, open next year's against the
next class, and carry `previous_enrollment_id` so the chain holds.

Two things to get right: it must be **reversible**, because a section promoted
by mistake is otherwise fixed by hand in the database; and running it twice must
not duplicate, which is the seeder lesson.

The new period's fee comes from the next class's structure, so this touches the
Fee module and should be built knowing that.

### S2 — School Leaving Certificate (TC)
A child cannot move to another school without one, and it is a legal document
here. Everything it prints — admission date, leaving date, class, date of birth,
conduct, whether fees were clear — exists in this system already, and T5 is the
record it would be built on.

### S3 — Sibling links
Fee concessions here are given by family ("second child 20%"), and the discount
module has nowhere to ask "how many of this family are enrolled". Guardians are
already deduplicated by phone, so the link exists in the data and is not exposed.

### S4 — A student ID card
The photograph is already uploaded and stored. The card is the same kind of
print view as the fee challan and the result card.

### S5 — Admission enquiry, before admission
A parent who visits in March is a record a school wants to keep and follow up,
and turning an enquiry into an admission should not mean typing it all again.

### S6 — Bulk section transfer
Moving fifteen children from 9-A to 9-B in October. Today that is fifteen
individual edits, and each one should be closing a period and opening another —
which, per T6, it does not.

---

## Waiting on this module

**Attendance and Exam both read `leave_date`.** T4 and T6 are not local: an
honest leaving date and a single open period are what those two modules assume
and cannot check for themselves.

---

## Carried forward

**The staff module.** `teacher_class_assignments` was built to close attendance
A13. The rest of the teacher's own record is in
[docs/STAFF-MODULE-PLAN.md](docs/STAFF-MODULE-PLAN.md), waiting on one decision
from you (Part 12: how a person doing two jobs is paid).

**The exam screens.** Grace marks, subject roles, the result card and the
datesheet are built and tested on the backend; the Vue screens are not built.

**PHPStan.** All 61 baseline errors are in this module —
`StudentService` (30), `StudentRepository` (18), `GuardianService` (4),
`Student` (4), the two requests (5). Almost all are one root cause: loops over
untyped collections, so PHPStan sees a bare `Model`. Worth clearing while we are
in here.
