# Production-Level User Testing Roadmap

Goal: har module ko real user ki tarah, end-to-end flow mein test karna (random clicks nahi — ek logical sequence, jaisa live school use karega).

Login details: `TEST-LOGINS-DEMO.md` use karo.

## Testing order (dependency-based — upar wale neeche walon ka data banate hain)

### Phase 1 — Foundation (Owner/Developer login)
1. **Settings: School Profile** — name/logo/contact update, save, reload confirm.
2. **Settings: Campuses** — add campus, edit, soft-delete, restore, reuse same name after delete.
3. **Settings: Campus Types** — create inline from Campus form + standalone.
4. **Settings: Sessions (Academic Year)** — create session, set active, dates required check.
5. **Settings: Classes & Sections** — add class, add sections under it, edit, delete.
6. **Settings: Subjects** — create, assign to class.
7. **Settings: Roles & Permissions** — verify each role's menu visibility (campus admin, teacher, accountant).
8. **Theme/Appearance** — change palette, confirm it reflects on login page + full site.

### Phase 2 — People (Campus Admin login, per campus)
9. **Staff: Create** — full form incl. documents upload, bank fields conditional on payment method, designation+role pick.
10. **Staff: List/Edit/Deactivate/Restore** — filters, soft delete, reuse name after delete.
11. **Staff: Teaching Assignments** — assign teacher to class/section/subject, "who can cover" check.
12. **Students: Create** — admission form, campus/class/section cascade, guardian link.
13. **Students: List** — filters (campus→class→section cascade, gender, status, search), status-update modal, bulk ID cards.
14. **Students: Documents** — upload, required/optional type check.

### Phase 3 — Money flows
15. **Fee: Fee Heads** — create, assign to class.
16. **Fee: Vouchers Generate** — bulk generate for session/class/month, year dropdown (2000–current+1).
17. **Fee: Vouchers Index** — filter, print, mark paid, partial payment.
18. **Staff: Payroll** — run month, mark paid (full/partial), give advance (lump/partial), auto-deduction across months, early return/waive, overpayment rejection check.
19. **Finance** — ledger/expense entries if applicable, verify totals reconcile with fee+payroll.

### Phase 4 — Daily operations
20. **Attendance** — mark for a class/section, edit same-day, view history/report.
21. **Exam** — exam types, (if built) exam creation → result entry → report card.
22. **Transport** — route/vehicle assign, student assignment.
23. **Inventory** — item add, stock in/out, low-stock check.

### Phase 5 — Cross-role / portal
24. **Teacher portal login** — own classes, own students, attendance entry only for assigned class.
25. **Student/Guardian portal login** — own fee vouchers, own attendance, own results, no access to others' data (403 check).
26. **Cross-campus isolation** — campus A admin must get 403 on campus B's data (students, staff, fee, payroll).

### Phase 6 — Edge cases (do these last, per flow above)
- Soft-delete + name-reuse on every "unique name" entity (campus, class, subject, session, staff/student doc types).
- Required-field validation on every form (submit empty, submit partial).
- Cache staleness: after `migrate:fresh --seed` + reseed, confirm list pages show correct counts (`php artisan cache:clear` if not).
- Dark mode + mobile width spot-check on 3–4 key pages (Students list, Payroll, Login, Staff Create).

## How to log results
For each numbered item: ✅ pass / ❌ fail (+ screenshot/error) / ⚠️ works but UX issue. Keep notes directly under each item here as you go, so progress persists across sessions.

---
## Progress Log
_(update as testing proceeds)_
