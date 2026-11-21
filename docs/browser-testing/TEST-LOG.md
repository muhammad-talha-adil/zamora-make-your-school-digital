# Browser Test Log (memory file)

READ THIS FIRST, then only the one prompt file you are working on. Update it after
EVERY page/role, not at the end. Keep rows short; no pasted page text.

App: http://localhost:8010 · password for every account: `123456` · DB: MySQL `zamora`
(demo data seeded 2026-11-22: 4 campuses, ~31 students, 18 staff, fee/exam/transport/inventory).
Status keys: `-` not started · `~` in progress · `OK` passed · `BUG` issues found · `BLK` blocked

## Progress by prompt file

| # | File | Status | Last role tested | Left to do |
|---|---|---|---|---|
| 00 | 00-START-HERE.md (setup, skills) | - | | |
| 01 | 01-shell-auth-roles.md | ~ | owner, developer | other 11 logins, teen-width access |
| 02 | 02-settings-setup.md | - | | |
| 03 | 03-students.md | - | | |
| 04 | 04-fee.md | - | | |
| 05 | 05-finance.md | - | | |
| 06 | 06-attendance.md | - | | |
| 07 | 07-exam.md | - | | |
| 08 | 08-staff-payroll.md | - | | |
| 09 | 09-timetable.md | - | | |
| 10 | 10-transport.md | - | | |
| 11 | 11-inventory.md | - | | |
| 12 | 12-portals.md | - | | |
| 13 | 13-ui-ux-consistency.md | - | | |

## Login matrix (works? / lands on)

| Login | Works | Lands on | Tested modules |
|---|---|---|---|
| owner@school.com | yes | /dashboard | shell only |
| developer@web.com | yes | /portal (404, see I-02) | shell only |
| principal@school.com (campus admin) | ? | | |
| headteacher@school.com | ? | | |
| teacher1@school.com | ? | | |
| accounts@school.com | ? | | |
| reception@school.com | ? | | |
| clerk@school.com | ? | | |
| driver1@school.com | ? | | |
| maid@school.com | ? | | |
| student.test@school.com | ? | | |
| guardian.test@school.com | ? | | |
| admin@school.com, admin2@school.com | NO - do not exist (super_admin role removed) | | |

## Issues (ID · page · role · what · severity H/M/L · state open/fixed)

- I-01 · /timetable · owner/developer · error page (owner), 500 (developer) · H · open (new module, verify with seeded campus data first)
- I-02 · login redirect · developer · lands on /portal -> 404 and error text leaks `App\Models\Student` · H · open
- I-03 · /dashboard · owner · hardcoded trend badges (+2.1%, +1.5%, +5.3%, +12%) next to 0 values · M · open
- I-04 · inventory dashboard · owner · "VALUE $0" but rest of app uses Rs. · L · open
- I-05 · all pages · owner · "No campus" banner while DB was empty (DB now seeded; re-check) · L · recheck

## Checked OK (one line each: page · role · date)

- Mobile 390px: sidebar off-canvas, no horizontal overflow on /students, /fee/vouchers, /settings/school-hub, /transport, /finance, /dashboard (owner, iframe simulation, 2026-11-22 · re-verify on real resize)
- Light/dark toggle works; sidebar stays dark in light mode

## Efficiency findings (task done in N clicks, could be M)

(none yet. format: `task · role · now N clicks · proposed M · how`)

## Notes for next session

- Browser skills to install are listed in 00-START-HERE.md; record here once installed: [ ]
- Work order = file numbers. Do not skip ahead; finish a file, mark the table, then next.
