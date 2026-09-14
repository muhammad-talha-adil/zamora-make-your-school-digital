# Test Logins — one per role

Every account below uses password **`123456`**. Seeded by `UsersSeeder`/`StaffSeeder`/
`StudentSeeder`/`GuardianSeeder` — re-run `php artisan db:seed` (or `migrate:fresh --seed`)
to get them back if the database is reset.

Status tag next to each role: **(Complete)** — every screen this role needs already exists and
is reviewed/fixed. **(Partial)** — the modules they work in are done, but a piece specific to
this role (usually a self-service Portal, Phase 2) hasn't been built yet. **(Not built)** — no
screen exists for this role's own work at all yet.

| Role | Email | Notes |
|---|---|---|
| Developer (Complete) | `developer@web.com` | Every permission in the system, always. |
| School Owner (Complete) | `owner@school.com` | Everything except developer-only system tooling. |
| Super Admin (Complete) | `admin@school.com` | School-wide, below the owner. |
| Campus Admin (Complete) | `admin2@school.com` or `principal@school.com` | The second has a real staff profile attached. |
| Head Teacher (Complete) | `headteacher@school.com` | Real staff profile (Farhan Sheikh, Academics/Head Teacher). `/staff/me` takes them to it; `/staff/teaching` shows their own assigned classes. |
| Teacher (Complete) | `teacher1@school.com` | Real staff profile, teaches Mathematics/Science. `/staff/me` takes them to their own profile; `/staff/teaching` shows only their own assigned classes. |
| Accountant (Complete) | `accounts@school.com` | Real staff profile. |
| Driver (Partial) | `driver1@school.com` | Real staff profile, `/staff/me` works for their own profile/attendance/leave/salary now — still no dedicated Transport "my route today" screen. |
| Receptionist (Complete) | `reception@school.com` | Real staff profile. |
| Clerk (Complete) | `clerk@school.com` | Real staff profile (Bilal Aslam, Administration/Clerk). |
| Maid (Complete) | `maid@school.com` | Real staff profile (Shabana Bibi, Support/Support Staff). Role is intentionally minimal, nothing further to build. |
| Student (Complete) | `majid.hussain1@student.com` | Has a real student record, fees, results to look at. |
| Student (Complete) — fixed test | `student.test@school.com` | Repointed onto a real, fully-seeded student — real fees/results/attendance to look at, memorable email. |
| Guardian (Complete) — fixed test | `guardian.test@school.com` | Repointed onto a real guardian with real linked children. |

For any other real guardian login, open any student in Student List → Guardians tab and use
that guardian's email (also password `123456`).

---

## What each role can actually do

Plain description, not permission names or URLs — what they can see and do, module by module.

### Developer (Complete)
Everything, everywhere, always — including the two things nobody else can touch: the
Artisan/Cache maintenance tools, and the Subscription/license control panel.

### School Owner (Complete)
Everything a school itself would ever do — students, fees, exams, attendance, staff, payroll,
inventory, transport, finance reports, the school's own branding/campuses. Cannot touch the
developer's subscription/system tooling.

### Super Admin (Complete)
Same day-to-day reach as the Owner, minus the things that belong to ownership specifically:
cannot change the school's branding or add/remove campuses, cannot set what anyone is paid or
release payroll (can only run it), cannot see owner-level financial reports, cannot delete
students/exams/fee vouchers/staff/inventory purchases, cannot assign the Owner-level role to
anyone.

### Campus Admin (Complete)
Full day-to-day control of their own campus: students (add/edit, not delete), attendance, exams,
fees, inventory, transport, staff (hire/manage/mark attendance, not see salaries or run payroll),
finance viewing and reports, user accounts for their campus. Cannot delete students, exams, fee
vouchers, or inventory purchases — those stay above this level.

### Head Teacher (Complete)
Everything a Teacher can do (below), plus: verify/countersign marks a teacher entered, manage exam
registrations, lock an attendance register for the day, view the whole student list (not just
their own classes), and manage the class timetable.

### Teacher (Complete)
Their assigned classes and subjects only: view their students, mark and edit attendance, run
attendance reports, view/manage exam papers for their subjects, enter marks, view results, view
the timetable, apply for their own leave and see their own basic staff record.

### Accountant (Complete)
Everything Fee and Finance related: vouchers, payments, receive/make payment screens, transaction
reports, cash book, income/expense statements. Can view students and inventory (read-only,
reports too). Can run payroll (generate it), but not set anyone's salary or release the actual
payment. Cannot delete a fee voucher or see owner-level financial reports.

### Student / Guardian (Complete)
Their own child's records only, through the family Portal (not the admin screens): their own fee
vouchers and payment history, their own exam results, their own attendance history, applying for
their own leave, and viewing the exam date-sheet. A guardian sees exactly what their child sees —
there's no separate guardian-only view.

### Driver (Partial)
Just enough to sign in and see their own basic record and their own assigned
route/vehicle — nothing else.

### Clerk (Complete)
Front-office admissions work: add and edit students (not delete), view fee vouchers and print
them (not collect payment), view attendance, view inventory. Sees their own basic staff record.

### Receptionist (Complete)
Front-desk visibility: view students, view attendance, view fee vouchers and print them, view
transport. Read-only across the board. Sees their own basic staff record.

### Maid (Complete)
Login only — sees their own basic staff record and nothing operational. No module access beyond
that.
