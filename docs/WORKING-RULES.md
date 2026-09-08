# Working rules for this codebase

Read this before changing or reviewing any module. It records what has already
gone wrong here, so the same class of bug is not reintroduced.

The project is a Pakistani school management system: multi-campus, session-based,
with fee, exam, attendance, inventory, staff and transport modules. Money and
student records are the sensitive parts.

---

## 1. Stop and ask

Do not "fix" any of these on your own judgement. Raise it and wait:

- A change to the **meaning** of stored data (what a column represents, how a
  status is decided, how a fee is calculated).
- Anything that would **lose history** — overwriting a row that other records
  point at, or dropping a column that holds past state.
- A **unique constraint** on live data, or removing one.
- A migration that **deletes or rewrites rows**.
- A business rule you had to guess at. Guessing produces a plausible system that
  is wrong in a way nobody notices for a year.

State the options, say which you would pick and why, then stop.

---

## 2. Before touching a module

Work through this, in order:

1. **Read the migrations for every table the module writes to.** Column types,
   nullability, defaults, and what the foreign keys actually point at.
2. **Check the live schema, not just the migrations.** They have disagreed here.
   `SHOW CREATE TABLE`, or query `information_schema`.
3. **Follow the write path end to end**: route → FormRequest → controller →
   service → repository → model. The real rules are usually in the repository.
4. **Read the enum and the column it is stored in together.** A value the code
   writes that the enum does not have raises a `ValueError` at the cast.
5. **Check what else reads the table.** A column changed here is a report broken
   there.

---

## 3. Schema rules

- **One create migration per table.** Later changes go in their own `Schema::table`
  migration, named for what they do.
- **Foreign keys must name their table explicitly**: `constrained('school_classes')`,
  never bare `constrained()`. Laravel infers `class_id` → `classes`, which does
  not exist here. The academic year is `academic_sessions`; `sessions` is
  Laravel's HTTP session store and its `id` is a varchar.
- **A foreign key may not point forward.** The referenced table must be created
  by an earlier migration. If it cannot be, add the constraint in a later
  migration — see `2026_09_07_000003_add_deferred_foreign_keys`.
- **Tables are InnoDB.** `config/database.php` states it. MyISAM ignores foreign
  keys and does not support transactions, so a `DB::transaction()` on MyISAM
  silently does not roll back.
- **Nullable is a decision, not a default.** A nullable foreign key means an
  orphan is allowed; say in a comment why that is acceptable.
- **If validation permits null, the column must too.** These drifted apart and
  admissions into a class without sections failed at the insert.

## 4. Session and history rules

The system must be able to answer, five years later: *where was this child, in
which class, on what fee, on this date?*

- `student_enrollment_records` is the pivot: **one row per enrollment period**,
  not per student.
- **One open period at a time** — `leave_date IS NULL` marks it. Enforced by a
  unique index; see `2026_09_07_000004_enforce_single_open_enrollment`.
- **A move opens a new period.** Class, section, campus or session changing means
  close the current row (`leave_date`) and create the next one with
  `previous_enrollment_id` pointing back. Never overwrite the placement in place.
- **A correction edits in place.** Fee amounts, descriptions, spelling — same row.
- Periods are **back to back**: the closed row's `leave_date` equals the new
  row's `admission_date`, so every date falls inside exactly one period.
- Transactional tables carry `session_id` directly, or reach it through their
  parent (`exam_papers` → `exams`) or through `student_enrollment_record_id`.
  When adding a table, pick one of those three and say which.

## 5. Identifiers

- `admission_no`, `registration_no` and `student_code` are **issued once and never
  change**. They are printed on vouchers and result cards. The edit form shows
  them read-only, and `UpdateStudentRequest` pins `admission_no` to the stored
  value so a tampered or stale form cannot rename it.
- The same applies to any other externally-visible number (voucher numbers,
  receipt numbers, employee numbers).

## 6. Validation rules

- **Rules live in a FormRequest**, never inline in a controller.
- **Which rule set applies is decided by the route**, never by a field in the
  payload. A caller must not be able to choose their own validation.
- **Do not define the same `$rules` variable twice.** The second assignment
  silently discarded thirteen guardian rules and broke every student edit.
- **A closure rule still runs after `date` or `numeric` has failed.** Guard it —
  `Carbon::parse()` on a bad string throws and turns a validation message into a
  500.
- **Create and update rules must agree** on whether a field is required. A field
  optional at create and required at update leaves records that can never be
  saved again.
- `validated()` returns only keys that have rules. A field with no rule silently
  disappears before it reaches the repository.

## 7. Roles and permissions

- **spatie/laravel-permission**, not a custom layer. Roles and abilities are
  seeded by `RolesSeeder` and `PermissionsSeeder`; the seeders are the source of
  truth, so change access there, not by hand in the database.
- Ability names are `<module>.<subject>.<action>`, e.g. `fee.voucher.generate`.
- `RolesSeeder` supports `*`, `module.*` and `-` to subtract, which is how owner
  and super_admin are expressed as "everything except".
- **A permission says what someone may do. A policy says whose records.** Campus
  and class scoping belong in policies and query scopes, not in the ability name.
- New routes get permission middleware. Most routes still only have `auth`; do
  not add to that backlog.

## 8. Frontend rules

- **No hardcoded colours.** Every colour comes from the palette in
  `theme_palette_colors`, through the tokens in `resources/css/app.css`. Use
  `bg-card`, `text-muted-foreground`, `bg-success/10`, `text-destructive` — never
  `bg-white`, `text-gray-500`, `bg-green-600`. The only exceptions are the print
  pages and the theme editor itself.
- **Table row actions use `RowActions` + `RowAction`.** Icon only, name on hover,
  same icon and colour for the same action in every table. Card layouts keep
  labelled buttons — a phone has no hover.
- **Responsive is not optional.** Tables scroll inside `.table-scroll`; grids
  step down (`grid-cols-1 sm:grid-cols-2`); toolbars wrap. Check that a 375px
  viewport has no horizontal page scroll.
- Both light and dark must work. Never define a colour only inside a `dark:`
  block.

---

## 9. Testing

Every change is tested. No exceptions.

**Layout** — one file per case, named for what it covers:

```
tests/Feature/<Module>/<Area>/Case_NN_WhatItCovers.php
tests/Feature/Student/Admission/Case_07_GuardianTest.php
```

Shared setup goes in `tests/Support/` (see `AdmissionWorld`), not duplicated
across files.

**What a case file contains**

- A file-level docblock saying *why* this behaviour matters to a school, not what
  the code does.
- Happy path, failure paths, and the awkward ones: boundaries, duplicates,
  missing optional fields, wrong references, authorisation.
- Datasets (`->with([...])`) for validation rules rather than repeated tests.

**Rules**

- Tests run on SQLite in memory. They never touch the working database.
- Assert on **outcomes** — rows written, values stored — not just the response
  code. `assertSessionHasNoErrors()` alone proves nothing was saved wrong.
- Use `assertForbidden()` / `assertNotFound()`, not `assertStatus(403)`.
- Do not weaken a test to make it pass. If a test fails, first establish whether
  the test or the code is wrong, and say which.
- Keep the reference data helper's generated values unique (CNIC, phone,
  admission number) so a test creating two records does not trip a uniqueness
  rule that is not what it is testing.

**Running**

```bash
php artisan test --compact tests/Feature/Student/Admission
php artisan test --compact --filter="guardian"
php artisan test --compact                      # whole suite
```

---

## 10. Before finishing

```bash
vendor/bin/pint --dirty          # format
vendor/bin/phpstan analyse       # static analysis, level 5
vendor/bin/psalm                 # second opinion
php artisan test --compact       # full suite
npm run build                    # if any .vue changed
```

Both static analysers are scoped to the module under review. When a module is
clean, add it to `phpstan.neon` and `psalm.xml` and leave it clean.

**Report honestly.** If tests fail, show the output. If something was skipped,
say so. A bug found and left unfixed is worth more said out loud than quietly
worked around.

---

## Appendix: bugs already found here

Kept as a record of what this codebase gets wrong, so a review knows where to
look. All were live in production.

| # | Bug | Effect |
|---|---|---|
| 1 | `assignment_type` written as `'custom'`, absent from the enum | Any admission with a monthly or annual fee returned 500 and saved nothing |
| 2 | `fee_structures.session_id` → HTTP `sessions` table | Foreign key never created; fresh install failed |
| 3 | `sections.class_id` → non-existent `classes` | Same |
| 4 | `Carbon::parse()` unguarded in a closure rule | An unparsable date returned 500 instead of a validation message |
| 5 | Every table MyISAM | No foreign keys anywhere; `DB::transaction()` never rolled back |
| 6 | `enrollment.section_id` NOT NULL while validation allowed null | Admission into a class without sections crashed |
| 7 | `father_relation_id` read unguarded | Sibling admission (linking an existing guardian) crashed |
| 8 | `$guardianRules` assigned twice | Thirteen guardian rules discarded; **every** student edit failed |
| 9 | Rule set chosen by an `action` field in the payload | Caller could select their own validation |
| 10 | Guardian matched strictly on phone during update | Correcting a phone number created a duplicate guardian and split the family |
| 11 | Custom role tables empty while data sat in Spatie's | Every role and permission check returned false |
| 12 | `--radius`, `--border`, `--ring` never defined | `rounded-*` and focus rings dead across the whole app |
| 13 | `ThemeSetting::$colors` had no accessor | Saved palette never applied |
| 14 | Enum column compared with `===` but never cast on the model (`StudentDiscount::$value_type`) | Every percentage concession applied as flat rupees: 10% took off Rs 10 |
| 15 | Controller method omitted a route parameter the route declares (`FeeStructureItemController::update`/`destroy`) | Arguments passed positionally, structure id landed in the item argument: **every charge edit and delete on the fee structure screen returned 500** |
| 16 | Booleans defaulted instead of preserved when a form omitted them | `is_optional` cleared and `applicable_on_admission` forced on with every item edit |
| 17 | `fee_vouchers.section_id` NOT NULL while `enrollment.section_id` is nullable | A student in a class without sections was never billed; the fee run caught the integrity error per student and only logged it |
| 18 | Request validated `items` that the controller never wrote (`FeeStructureController::update`) | An API client or import got a success response with the charges silently dropped |
| 19 | A date column cast to `date` compared against a plain `Y-m-d` string | The stored value carries a zero time part, so the match fails. Registers and holidays were never found — invisible on MySQL, which coerces; fatal on SQLite. Use `whereDate` |
| 20 | `'status' => 'enum'` as a cast | Not a Laravel cast at all: `InvalidCastException` the moment the attribute is read, so `StudentLeave` could not be touched |
| 21 | A method type-hinted `Eloquent\Collection` receiving `collect()` | The attendance class report returned a 500 whenever one student had no records — the ordinary case |
| 22 | An index named `unique_*` declared with `index()` instead of `unique()` | Two attendance rows for one student on one day, in a table whose name promised otherwise |
| 23 | A `TIME` column cast as `datetime` | The clock time gained an arbitrary date part and Eloquent wrote a full `Y-m-d H:i:s` back into it |
| 24 | A model method named `is()` | Collides with Eloquent's own `Model::is($model)`; PHP refuses the class outright |
| 25 | A controller writing columns the table does not have (`ExamRevaluationController::request`) | Four of the five fields were invented; **every recheck submission threw**, and the list screen filtered on a fifth |
| 26 | `hasMany()` on a column Eloquent does not guess (`request_id`, not `exam_revaluation_request_id`) | The relation returned an empty collection forever, so no revaluation had any history |
| 27 | A timestamp written without the status that goes with it (`published_at`, no `status`) | A screen filtering on status and a screen reading the timestamp gave different answers about the same exam |
| 28 | A derived status recalculated on every save (`ExamService::update`) | Editing a published exam's name silently unpublished it, and reopening one for marking was undone by the next save |
| 29 | Trusting an id the client posted (`enrollment_id` on the marking grid) | The caller could name any enrollment they liked and walk straight past the roll check |
| 30 | Absent and exempt treated as the same thing | A missed paper was taken out of *both* sides of the percentage: a child who sat one paper of eight was reported at 90% and graded A |

**Enum or open list — decide, and say which.** A fixed set the application
reasons about (fee frequency, voucher status) is a backed enum and the column
is cast. A list the *school* extends (`attendance_statuses`) stays a plain
string column: cast it and the first row a school adds throws a `ValueError`
on read. Where both are true — the app knows five codes, the school may add a
sixth — the enum names the known ones and the behaviour is read from the row
(a `weight`), never from testing the code.

**Seeders must be idempotent.** `DiscountTypeSeeder` used `create()`, so a
second run duplicated every concession a school had already started using.
Seed with `updateOrCreate()` keyed on the natural key — the code, not the id.

**A third lesson.** Bug 17 hid behind a `try`/`catch` that logged and moved on.
When a loop catches per-item errors, a whole class of records can fail forever
without anyone seeing it — assert on the returned `errors` array in tests, not
just on the happy path.

**A fourth lesson: a policy without a scope is not a fence.** The exam module
had neither, and both had to arrive together. A policy guards one record; a
query scope (`visibleTo`) filters a list. Add only the policy and every list
screen still hands out what the record screen refuses — the marking grid, the
results list, the dashboard's toppers. Add both, and write a test that asserts
they agree.

**A fifth: refuse with the reason.** `publish()` accepted an exam with no marks
in it. The fix is not a boolean — a school told only "not ready" will press the
button again. `readinessOf()` returns the list ("no papers on the timetable",
"6 of 40 children have no marks at all"), and a deliberate `force` exists for
the school that means it anyway. That is a decision somebody took, rather than
one the software made quietly.

**Two lessons that keep repeating.**

*Casting.* Bugs 1, 14 and the G1 frequency bug are all the same mistake: an enum
compared with `===` against a column the model never cast. When a comparison
against an enum case looks right and behaves as if the branch is never taken,
check `casts()` on the model before anything else.

*Assertions that hide failures.* Bug 15 sat behind a passing test:
`assertSessionHasNoErrors()` succeeds on a 500, because a server error puts
nothing in the session. Assert what the response should actually be
(`assertRedirect()`, `assertSuccessful()`, `assertNotFound()`), and reach for
`withoutExceptionHandling()` when a route behaves as if it did nothing.
