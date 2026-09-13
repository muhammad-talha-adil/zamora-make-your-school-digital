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

# Current module

None in progress. Inventory — the last unreviewed area — closed
2026-09-14; full findings are in [docs/MODULE-LOG.md](docs/MODULE-LOG.md)
under "Module: Inventory".
