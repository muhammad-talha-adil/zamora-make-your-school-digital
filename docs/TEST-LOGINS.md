# Test Logins — one per role

Every account below uses password **`123456`**. Seeded by `UsersSeeder`/`StaffSeeder`/
`StudentSeeder`/`GuardianSeeder` — re-run `php artisan db:seed` (or `migrate:fresh --seed`)
to get them back if the database is reset.

| Role | Email | Notes |
|---|---|---|
| Developer | `developer@web.com` | Every permission in the system, always. |
| School Owner | `owner@school.com` | Everything except developer-only system tooling. |
| Super Admin | `admin@school.com` | School-wide, below the owner. |
| Campus Admin | `admin2@school.com` or `principal@school.com` | The second has a real staff profile attached. |
| Head Teacher | `headteacher@school.com` | Bare account, no staff profile — login only. |
| Teacher | `teacher1@school.com` | Real staff profile, teaches Mathematics/Science. |
| Accountant | `accounts@school.com` | Real staff profile. |
| Driver | `driver1@school.com` | Real staff profile. |
| Receptionist | `reception@school.com` | Real staff profile. |
| Clerk | `clerk@school.com` | Bare account, no staff profile — login only. |
| Maid | `maid@school.com` | Bare account, no staff profile — login only. |
| Student (real data) | `majid.hussain1@student.com` | Has a real student record, fees, results to look at. |
| Student (blank test) | `student.test@school.com` | No linked student record — portal will say so. |
| Guardian (blank test) | `guardian.test@school.com` | No linked child — portal will say so. |

For a real guardian login with a real child attached, open any student in Student List →
Guardians tab and use that guardian's email (also password `123456`).

---

## What each role can actually do

Plain description, not permission names or URLs — what they can see and do, module by module.

### Developer
Everything, everywhere, always — including the two things nobody else can touch: the
Artisan/Cache maintenance tools, and the Subscription/license control panel.

### School Owner
Everything a school itself would ever do — students, fees, exams, attendance, staff, payroll,
inventory, transport, finance reports, the school's own branding/campuses. Cannot touch the
developer's subscription/system tooling.

### Super Admin
Same day-to-day reach as the Owner, minus the things that belong to ownership specifically:
cannot change the school's branding or add/remove campuses, cannot set what anyone is paid or
release payroll (can only run it), cannot see owner-level financial reports, cannot delete
students/exams/fee vouchers/staff/inventory purchases, cannot assign the Owner-level role to
anyone.

### Campus Admin
Full day-to-day control of their own campus: students (add/edit, not delete), attendance, exams,
fees, inventory, transport, staff (hire/manage/mark attendance, not see salaries or run payroll),
finance viewing and reports, user accounts for their campus. Cannot delete students, exams, fee
vouchers, or inventory purchases — those stay above this level.

### Head Teacher
Everything a Teacher can do (below), plus: verify/countersign marks a teacher entered, manage exam
registrations, lock an attendance register for the day, view the whole student list (not just
their own classes), and manage the class timetable.

### Teacher
Their assigned classes and subjects only: view their students, mark and edit attendance, run
attendance reports, view/manage exam papers for their subjects, enter marks, view results, view
the timetable, apply for their own leave and see their own basic staff record.

### Accountant
Everything Fee and Finance related: vouchers, payments, receive/make payment screens, transaction
reports, cash book, income/expense statements. Can view students and inventory (read-only,
reports too). Can run payroll (generate it), but not set anyone's salary or release the actual
payment. Cannot delete a fee voucher or see owner-level financial reports.

### Student / Guardian
Their own child's records only, through the family Portal (not the admin screens): their own fee
vouchers and payment history, their own exam results, their own attendance history, applying for
their own leave, and viewing the exam date-sheet. A guardian sees exactly what their child sees —
there's no separate guardian-only view.

### Driver
Just enough to sign in and see their own basic record and their own assigned
route/vehicle — nothing else.

### Clerk
Front-office admissions work: add and edit students (not delete), view fee vouchers and print
them (not collect payment), view attendance, view inventory. Sees their own basic staff record.

### Receptionist
Front-desk visibility: view students, view attendance, view fee vouchers and print them, view
transport. Read-only across the board. Sees their own basic staff record.

### Maid
Login only — sees their own basic staff record and nothing operational. No module access beyond
that.
