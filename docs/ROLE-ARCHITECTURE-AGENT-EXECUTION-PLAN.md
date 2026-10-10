# Role Architecture Redesign — Agent Execution Plan

Ye file `ROLE-ARCHITECTURE-REDESIGN-PLAN.md` (the "what to build") ka operational saathi hai — **"kis tarah banayenge"**: har phase ka complete flow, step-by-step, aur agar sab kaam ek hi session/flow mein chalana ho to kitne agents, kitne sub-agents, aur unke neeche kitne aur agents lagenge — har ek ka apna naam.

---

## Command structure (top se neeche)

```
Supervisor (Nazim) — main, sab phases ko sequence mein chalata/review karta hai
 │
 ├── Phase Lead 1 — "Kafeel" (Campus Switcher)
 ├── Phase Lead 2 — "Zabit" (1-Admin-per-Campus + Owner-only guard)
 ├── Phase Lead 3 — "Wakeel" (Staff Permissions Page)
 └── Phase Lead 4 — "Nigran" (Menu Permission-tagging)
```

`super_admin` removal (plan ka Section 1) **already complete** hai — is liye is execution plan mein uska koi phase nahi, seedha Phase 2 (Campus Switcher) se shuru.

Har Phase Lead apne neeche fixed chain follow karta hai (zamora-module-workflow ka standard order): **architect → (migration-builder) → (model-builder) → backend-builder → frontend-builder → security-auditor → test-writer → qa-runner**. Jahan migration/model ki zaroorat nahi (e.g. session-based ya pehle se maujood table), wo step skip hota hai — chain lamba nahi hota sirf is liye ke "complete" dikhe.

**Total agent count agar sab ek hi flow mein chalaya jaye:**

| Level | Count |
|---|---|
| Supervisor | 1 |
| Phase Leads (sub-agents of Supervisor) | 4 |
| Worker agents (sub-agents of Phase Leads) | 23 |
| **Grand total** | **28** |

---

## Phase 2 — Campus Switcher

**Phase Lead: "Kafeel"** (Nigran-e-Phase for campus switcher)

### Flow
1. Owner/Developer login karta hai → agar session mein `viewing_campus_id` nahi, pehla active campus auto-assign.
2. Header mein campus-dropdown dikhta hai (koi "All Campuses" option nahi) — current campus naam ke saath.
3. User doosra campus select karta hai → `POST /settings/switch-campus` → session update → page reload → **poora panel** (students, staff, fee, attendance, exam, inventory, transport — sab) ab sirf us campus ka data dikhata hai.
4. Campus Admin ko ye dropdown kabhi nahi dikhta — unka campus hamesha fix hai.

### Sub-agents (6)

| # | Name | Role | Kaam |
|---|---|---|---|
| 1 | **Naqsha** | architect (read-only) | `ScopesCampusForUser`, `ChecksSchoolReach`, `HandleInertiaRequests`, `AppShell.vue` ka exact touch-point map banata hai |
| 2 | **Bunyaad** | backend-builder | `resolveCampusId()` + `reaches()` mein session-fallback add karta hai, `CampusSwitchController` + route finalize karta hai (default-campus auto-assign logic samet) |
| 3 | **Nuqoosh** | frontend-builder | `CampusSwitcher.vue` dropdown banata hai, `AppShell.vue` header mein wire karta hai, shared `campuses`/`activeCampusId` props use karta hai |
| 4 | **Pehra** | security-auditor (read-only) | Confirm karta hai Campus Admin ko switcher kisi route se bhi accessible nahi, aur koi campus-restricted user session-override se escape na kar sake |
| 5 | **Kaatib** | test-writer | Pest tests: owner switch karke sirf us campus ka data dekhe, campus_admin ko switcher na mile, default-campus auto-assign | 
| 6 | **Nigraan-e-Akhir** | qa-runner | Narrow tests + pint + `npm run build` chalata hai, Phase Lead ko result deta hai |

---

## Phase 3 — 1-Admin-per-Campus + "sirf Owner badal sake"

**Phase Lead: "Zabit"**

### Flow
1. Owner (ya koi aur) Staff ko `campus_admin` role assign karne ki koshish karta hai.
2. System check karta hai: us campus ka koi aur active `campus_admin` to nahi?
   - Agar hai → dialog: "Is campus ka admin pehle se [Name] hai. Unhe (a) naya role do, ya (b) deactivate karo?" — **Owner khud choice karta hai**, koi auto-demote nahi.
   - Agar nahi hai → seedha assign ho jata hai.
3. Jab bhi `campus_admin` role **change/remove** ho raha ho (kisi ka bhi), backend policy check karta hai: request karne wala sirf `owner`/`developer` hai? Nahi to 403 — chahe UI se ho ya direct API call se.

### Sub-agents (6)

| # | Name | Role | Kaam |
|---|---|---|---|
| 1 | **Jaiza** | architect (read-only) | Role-assignment ka current endpoint/request/policy map karta hai (StaffController ya jo bhi role.assign karta hai) |
| 2 | **Qanoon** | backend-builder | Validation rule (1-admin-per-campus) + policy hard-check (`campus_admin` role change sirf owner/developer) implement karta hai |
| 3 | **Khidmat** | frontend-builder | "Replace admin" confirm-dialog (naya role do / deactivate karo) banata hai, role-assign form mein wire karta hai |
| 4 | **Pehra-2** | security-auditor (read-only) | Confirm: koi bhi non-owner route/API se `campus_admin` role change na kar sake, even via direct POST |
| 5 | **Kaatib-2** | test-writer | Tests: duplicate-admin block, owner-only-change 403 for everyone else, replace-flow (role-change + deactivate dono scenarios) |
| 6 | **Nigraan-2** | qa-runner | Narrow tests + pint + build |

---

## Phase 4 — Per-Staff-Member Permission Page (sabse bada phase)

**Phase Lead: "Wakeel"**

### Flow
1. Campus Admin (apne campus ke staff ke liye) ya Owner/Developer (kisi ke bhi liye) "Staff → Permissions" page kholta hai.
2. Staff-member search/select karta hai.
3. System ki **saari permissions** module-wise grouped checklist mein dikhti hain, jo already us staff ko mili hain wo ticked hain — **sirf "admin-only" permissions (users.role.assign, school.campus.*, staff.salary.manage, etc.) list mein nahi dikhti/disabled hain**.
4. Admin jo chahe tick/untick karta hai, Save karta hai → `syncPermissions()` us staff member pe seedha apply ho jati hai.
5. Agli dafa wo staff member login kare → sidebar/menu usi ke mutabiq adjust (Phase 5 se link).

### Sub-agents (6)

| # | Name | Role | Kaam |
|---|---|---|---|
| 1 | **Fehrist** | architect (read-only) | Saari existing `permissions` table ka module-wise breakdown banata hai, endpoint/page shape design karta hai |
| 2 | **Dastak** | backend-builder | `GET/PUT /staff/{staffProfile}/permissions` endpoints, admin-only-permission server-side strip-guard, policy (campus-scoped Campus Admin + unrestricted Owner/Developer) |
| 3 | **Chaukhat** | frontend-builder | `Staff/Permissions/Index.vue` — staff-picker + search-bar + grouped checklist UI |
| 4 | **Pehra-3** | security-auditor (read-only) | Confirm admin-only list kabhi bypass nahi ho sakti (request tampering, mass-assignment), aur ke cross-campus staff ko Campus Admin permission na de sake |
| 5 | **Kaatib-3** | test-writer | Tests: grant/revoke permission, admin-only list reject, cross-campus 403, sidebar visibility change ke saath |
| 6 | **Nigraan-3** | qa-runner | Narrow tests + pint + build |

---

## Phase 5 — Sidebar/Menu Permission-tagging

**Phase Lead: "Nigran"**

### Flow
1. `menus` table mein naya nullable `permission` column add hota hai.
2. Har existing menu-row (~34) ko uski matching permission se tag kiya jata hai (e.g. "Add Student" → `student.create`) — ye mapping khud decide/apply ki jayegi (client ne confirm kiya "khud kar lo").
3. `HandleInertiaRequests` ka menu-filter ab **role OR permission** dono check karta hai — jo bhi match ho, menu dikh jata hai.
4. Result: agar kisi staff member ko Phase 4 ke permission-page se ek extra ability di gayi ho jo uske role mein nahi thi, to uski sidebar mein wo menu-item **automatically** aa jata hai — koi extra kaam nahi.

### Sub-agents (5)

| # | Name | Role | Kaam |
|---|---|---|---|
| 1 | **Rasta** | architect (read-only) | `MenuSeeder` ke sab ~34 rows ka permission-mapping draft banata hai (route → permission cross-reference) |
| 2 | **Buniyad-2** | migration-builder | `menus` table mein `permission` column ki migration |
| 3 | **Tanzeem** | backend-builder | `MenuSeeder` rows ko tag karta hai, `HandleInertiaRequests` filter ko role-OR-permission banata hai |
| 4 | **Kaatib-4** | test-writer | Tests: role-based visibility tootay na, naya permission-based visibility kaam kare, dono ek sath (OR condition) |
| 5 | **Nigraan-4** | qa-runner | Narrow tests + pint + build, **poore system ka final smoke-check** (sab 4 phases ek sath integrate hone ke baad) |

---

## Agar sab ek hi flow mein sequentially chalana ho (recommended order)

```
Supervisor
 └─ Phase 2 (Kafeel: 6 agents) → commit/test pass
     └─ Phase 3 (Zabit: 6 agents) → commit/test pass
         └─ Phase 4 (Wakeel: 6 agents) → commit/test pass
             └─ Phase 5 (Nigran: 5 agents) → commit/test pass → FINAL smoke-check
```

Sequential isliye zaroori hai kyunki Phase 4 (permission page) aur Phase 5 (menu tagging) ek doosre se directly linked hain — Phase 5 ka `permission`-based filter Phase 4 ke diye hue permissions ko hi test kar sakta hai.

**Parallel possible kahan hai:** Phase 2 aur Phase 3 ek doosre se independent hain (alag files, alag concern) — in dono ke Phase Leads **parallel** chalaye ja sakte hain Supervisor ke neeche agar speed chahiye, lekin dono commit hone ke baad hi Phase 4 shuru ho (wo role-assignment aur campus-scoping dono pe depend karta hai).

---

## Final tally

- **1** Supervisor
- **4** Phase Leads: Kafeel, Zabit, Wakeel, Nigran
- **23** Worker agents (6 + 6 + 6 + 5)
- **= 28 agents total**, agar poora redesign ek hi coordinated flow mein chalaya jaye.
