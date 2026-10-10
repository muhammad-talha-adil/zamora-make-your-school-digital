# Role Architecture Redesign — Implementation Plan

Client ki demand ka maqsad: Owner = sab campuses ka ek dashboard (campus-switcher ke saath), har campus ka sirf 1 Campus Admin jo apne staff ko koi bhi granular permission de sake, `super_admin` role khatm, Student/Guardian ka ek hi portal (already hai), Developer untouched.

**Good news jo survey se mili:** System ka architecture already bohot centralized hai — `isSuperAdmin()` aur `isCampusRestricted()` dono sirf **ek hi jagah** (`User` model) check hote hain, aur 61 files sirf unhi 2 methods ko *call* karti hain. Isliye `super_admin` hatana 61 files edit karna nahi hai — sirf 1 array se ek entry hatana hai. Changes expected se bohot kam hain.

---

## 1. `super_admin` role khatam karna

**Change (tiny, central):**
- `app/Models/User.php` → `isSuperAdmin()`: `hasAnyRole(['developer','owner','super_admin'])` se `'super_admin'` hata dena. Bas isi ek line se saari 61 files automatically naya behavior le leti hain (koi aur file edit nahi karni).
- `database/seeders/RolesSeeder.php`: `super_admin` role definition hata dena (ya rehne dena but seed na karna — migration reason: existing DB rows).
- **Data migration (zaroori):** jo existing users ke paas `super_admin` role hai unko kisi role pe move karna hoga — on launch, ek Artisan command/migration jo `super_admin` role-holders ko `campus_admin` assign kare (unke apne campus pe) aur purana role detach kare, phir role record delete.

**Kahan touch nahi karna:** Policies/Controllers/Models jo `isSuperAdmin()` call karte hain — unko chhedne ki zaroorat nahi, wo sab automatically theek ho jayenge.

---

## 2. Owner: Campus-switcher (header dropdown)

**Abhi:** Owner/developer ke liye `resolveCampusId()` hamesha `null` return karta hai (sab campuses ka unfiltered data), koi switcher UI nahi hai.

**Naya:** Owner **aur Developer** dono ke header mein ek campus-dropdown — jis campus pe click karen, poora admin panel sirf usi campus ka data dikhaye.

**✅ Confirmed (client ka jawab):** Dropdown mein **"All Campuses" option bilkul nahi hoga.** Owner/Developer hamesha kisi **ek specific campus** pe "viewing" state mein hon ge — na ke combined/unfiltered multi-campus view mein. Pehli dafa login par (ya jab tak session mein koi campus na chuna ho) default **pehla active campus** (ya last-viewed, session persist ke through) auto-select ho jayega — kabhi bhi "no campus selected" state nahi hogi.

**Implementation (session-based, minimal):**
- Naya session key: `viewing_campus_id` (session mein store, cookie jaisa `sidebar_state` already hota hai) — **hamesha ek valid campus id hoga, kabhi `null`/empty nahi** (login ke waqt pehla campus auto-set).
- `app/Http/Controllers/Concerns/ScopesCampusForUser.php::resolveCampusId()` — owner/developer ke case mein ab `session('viewing_campus_id')` ko priority dena.
- `app/Policies/Concerns/ChecksSchoolReach.php::reaches()` — same treatment taake policies bhi switcher ko respect karen.
- `HandleInertiaRequests` shared props mein `campuses` list + `activeCampusId` add karna (header dropdown ke liye), aur agar session mein kuch set nahi to yahin pehla campus auto-assign kar dena.
- Naya chhota route: `POST /settings/switch-campus` → session set → redirect back. **Koi "clear"/"all campuses" variant nahi** — sirf ek campus se doosre campus pe switch.
- Frontend: `AppShell.vue`/header mein naya `CampusSwitcher.vue` dropdown component (owner + developer role ke liye conditionally render, campus list mein koi "All" entry nahi).
- **Campus Admin ko ye dropdown bilkul nahi dikhega** — unka `resolveCampusId()` pehle se hi unke apne campus pe force hai (`isCampusRestricted()` branch), switcher logic unhe touch hi nahi karega.

---

## 3. Har campus ka sirf 1 Campus Admin — aur sirf Owner usay badal sakta hai

**Naya constraint (2 hisse):**
1. Jab kisi staff member ko `campus_admin` role diya jaye, system check kare ke us campus ka koi aur active `campus_admin` to nahi (agar hai, to purane se role hata kar naye ko do, ya error de kar user se confirm karwaye).
2. **Clarification:** Campus Admin ka role **change/update/remove sirf Owner kar sakta hai — koi aur nahi.** Na khud Campus Admin apna role badal sakta hai, na koi staff member, na ek Campus Admin doosre campus ke admin ko. Ye ek **authorization rule** hai, sirf UI-level filter nahi.

**Implementation:**
- Jahan role assignment hota hai (Staff role-assign request/controller), ek validation rule: `campus_admin` role + campus pe already koi aur user ye role rakhta hai → block karo aur Owner se poocho.
- **✅ Confirmed (client ka jawab):** Auto-demote **nahi** hoga. Jab Owner kisi campus pe naya admin assign kare aur wahan pehle se koi admin ho, system ek dialog dikhaye jahan **Owner khud decide kare**: (a) purane admin ko koi aur role do (dropdown se operational role chuno — teacher/accountant/clerk/etc.), ya (b) purane admin ko **deactivate** kar do. Koi bhi silent/automatic switch nahi — Owner ki explicit choice hamesha chahiye.
- **Policy/Gate level enforcement:** role-assign endpoint mein ek explicit check — agar target role `campus_admin` hai (assign ho raha ho ya already kisi user ka hai jo change ho raha ho), to `Gate::authorize` sirf `owner`/`developer` ko pass kare; campus_admin khud bhi is action pe 403 khaye. Ye sirf frontend dropdown filter nahi, backend policy mein hard-coded hona chahiye taake direct API call se bhi bypass na ho sake.

---

## 4. Staff = ek hi role, baaki sab uske "sub-roles" (designations) — kabhi bhi Owner/Campus Admin ke barabar nahi

**Clarification (important restructure):** Abhi system mein `teacher`, `head_teacher`, `accountant`, `clerk`, `driver`, `receptionist`, `maid` — ye sab **top-level spatie roles** hain, `campus_admin` ke hi parallel/sibling level pe. Client ki demand hai ke ye sab asal mein **ek hi "staff" role ke sub-roles/designations** hon — aur koi bhi sub-role, chahe jitni bhi permissions di jayen (Section 5 ka permission-page se), kabhi bhi `owner` ya `campus_admin` ke **equal ya us se zyada powerful** na ban sake.

**✅ Confirmed (client ka jawab): Option A.** Spatie roles jaisa hai waisa hi rehta hai (teacher/accountant/etc. apne naam ki roles, kyunki policies already inhi naamo se wired hain). Sirf ye guarantee add hogi: **koi bhi staff-level role (`campus_admin`/`owner`/`developer` ke ilawa sab) kabhi bhi admin-level permissions (`users.role.assign`, `school.campus.manage`, `staff.salary.manage`, etc.) nahi le sakta** — chahe Section 5 ke permission-page se manually tick kyun na kiya jaye. Ye ek **permission-page ka server-side guard** hai: jab admin kisi staff member ko permission de raha ho, to "admin-only" tag wali permissions us checklist mein **dikhai hi na den** (ya disabled + greyed-out hon with tooltip "Owner-only ability").

(Option B — ek naya `staff_designations` table + sab ko sirf ek `staff` spatie role — **rejected**, bohot bada migration tha dozens of policy files ke liye, zaroorat nahi.)

**Staff Create/Edit role-dropdown:** role-dropdown sirf operational/designation roles dikhaye (teacher, head_teacher, accountant, clerk, driver, receptionist, maid) — `campus_admin`/`owner`/`developer` is list mein kabhi na hon, taake koi staff-level user galti se khud ko ya kisi aur ko admin-level na bana sake. (Ye dropdown-level filter Section 3's hard policy-check ka **supplement** hai, replacement nahi.)

---

## 5. Naya: Per-staff-member Permission Page (sabse bada naya feature)

Ye client ki demand ka core hai: admin ek staff member select kare, system ki **har** permission (add student, edit student, print voucher, inventory add, etc.) ek checklist + search-bar ke sath dikhe, admin jo chahe tick kar sake — role se **independent**, aur uske mutabiq sidebar/pages sirf wahi dikhen jo allowed hain.

### Backend
- spatie/laravel-permission already direct per-user permission assignment support karta hai (`$user->givePermissionTo()`/`syncPermissions()`) — abhi sirf roles ke zariye use ho raha hai, production mein koi UI nahi.
- Naya endpoint: `GET /staff/{staffProfile}/permissions` (list all permissions grouped by module, + currently-granted ones) aur `PUT /staff/{staffProfile}/permissions` (sync direct permissions).
- Policy/Gate: sirf `campus_admin` (apne campus ke staff ke liye) + developer/owner.
- Permission list grouping: naming convention already `module.action` hai (e.g. `fee.voucher.delete`, `exam.marks.enter`) — module prefix se group banana trivial hai (`explode('.', $name)[0]`).
- **Admin-only permission guard (Section 4 se link):** **✅ Confirmed list** (client ne is draft list ko as-is approve kar diya) — `users.role.assign`, `users.delete`, `school.campus.manage`, `school.campus.delete`, `school.profile.manage`, `school.theme.manage`, `staff.salary.manage`, `staff.payroll.approve`, `finance.reports.owner`. Backend `PUT` endpoint is list ko kabhi accept nahi karega (silently strip), chahe request mein aa bhi jaye — defense-in-depth, sirf frontend hide pe bharosa nahi.
- **Existing/new staff ki default permissions (✅ confirmed):** Project abhi production mein nahi hai, is liye koi complex per-user migration nahi chahiye. Sirf **ek basic default permission-set** define karna hai jo har naye staff member ko (unke role ke hisaab se) automatically mil jaye seeding/create ke waqt — Owner/Campus Admin baad mein Staff Permissions page se isi set ko aage customize kar sakte hain. (Simplify: seeded role permissions hi is "basic default set" ka kaam karte hain — jab staff create ho, unke role ki default permissions hi unki starting point hon, Permission-page sirf *extra*/override ke liye use ho.)

### Frontend
- Nayi page: `Staff/Permissions/Index.vue` — staff-picker (search) + checklist (grouped accordion by module, har group ke andar search-filterable checkboxes) + Save button.
- Reuse existing `FilterCard`/search patterns already present in app (ItemsStock, StaffController listings) — koi naya design-system nahi chahiye.

### Sidebar/menu visibility (zaroori architecture change)
**Abhi:** `HandleInertiaRequests` menu filter sirf `role` column check karta hai (`$menu->role === null || $user->hasAnyRole(...)`), **permission nahi dekhta**.

**Naya:** Direct-permission se user ko role ke bahar ki ability mil sakti hai (e.g. ek clerk ko "print voucher" di jaye jo uske role mein nahi) — agar menu abhi bhi sirf role check kare, to wo page milegi (route permission-gated hai) lekin sidebar mein link nahi dikhega. Isliye:
- `menus` table mein naya nullable column: `permission` (jaisa `role` column hai).
- `HandleInertiaRequests::share()` ka filter: `($menu->role === null || hasAnyRole) || ($menu->permission === null || hasPermissionTo($menu->permission))` — yani role-based ya permission-based, jo bhi match ho.
- `MenuSeeder` mein har menu item pe uska corresponding permission tag karna (e.g. "Add Student" menu → `student.create`).
- Ye sab **additive** hai — purana role-based filtering tootega nahi, sirf permission-based OR add ho raha hai.

---

## 6. Student/Guardian portal

**Koi change nahi chahiye.** Survey confirm karta hai: already ek hi shared portal (`routes/portal.php`), dono roles ek jaisi permissions rakhte hain, `ResolvesOwnStudent` trait se student data resolve hota hai — client ki demand already poori hai.

---

## 7. Developer role

**Koi change nahi.** `isSuperAdmin()` array mein `'developer'` as-is rehta hai.

---

## Summary — Kya badlega, Kya nahi

| Area | Change | Status |
|---|---|---|
| `User::isSuperAdmin()` | 1 line: `super_admin` hatana | ✅ Done |
| `RolesSeeder` | `super_admin` role definition hatana + data-migration (`retireSuperAdmin()`) existing holders ke liye | ✅ Done |
| `ScopesCampusForUser` / `ChecksSchoolReach` | session-based `viewing_campus_id` override add karna (owner/developer only) | 🔧 In progress (backend wiring done, route+controller done) |
| Header/AppShell | naya `CampusSwitcher.vue` dropdown + shared `campuses`/`activeCampusId` props | ⬜ Not started |
| Role-assignment: 1-admin-per-campus | validation rule | ⬜ Not started |
| Role-assignment: **sirf Owner Campus Admin badal sake** | policy/gate hard-check (Section 3) | ⬜ Not started |
| Staff-level roles kabhi admin ke equal na hon | permission-page server-side guard (Section 4, Option A) | ⬜ Not started |
| Staff role dropdown | admin-level roles list se chhupana | ⬜ Not started |
| **Naya:** Staff Permissions page | backend endpoint + frontend page (sabse bada kaam) | ⬜ Not started |
| `menus` table + seeder | naya `permission` column, existing rows tag karna | ⬜ Not started |
| `HandleInertiaRequests` menu filter | role OR permission check | ⬜ Not started |
| Portal | **koi change nahi** | — |
| Developer role | **koi change nahi** | — |

**Effort ka andaza (bade se chhote):** Staff Permissions page + admin-only guard (naya feature, sabse zyada kaam) > Campus switcher (medium) > Menu permission-tagging (medium, bohot saare menu rows tag karne honge) > 1-admin-per-campus + owner-only-change guard (chhota) > super_admin removal (✅ done, chhota tha).

**Suggested build order:** (1) ✅ super_admin removal → (2) campus switcher → (3) 1-admin-per-campus + owner-only-change guard → (4) Staff Permissions backend+page (admin-only guard included) → (5) menu permission column + tagging.

---

## Decisions (confirmed by client) — ab sab clear hai, implementation ke liye ready

1. Campus-switcher mein **"All Campuses" option nahi hoga** — hamesha ek specific campus select rehta hai (default: pehla campus, session mein persist).
2. Purana Campus Admin replace hone par **auto-demote nahi** — Owner se ek dialog ke zariye poocha jayega: naya role do, ya deactivate karo.
3. Section 4 → **Option A** confirmed (roles jaisi hain waisi rahengi, sirf admin-only-permission guard add hoga).
4. Admin-only permission list **as-drafted confirmed** (Section 5 mein list dekhen).
5. Koi per-user migration nahi chahiye (project production mein nahi hai) — har naye staff ko uske **role ki seeded default permissions** hi starting point ke tor pe milti hain; Permission-page sirf customization/override ke liye.
6. Campus-switcher **Developer ko bhi** milega (Owner jaisa hi).
7. Sidebar menu → permission mapping **khud decide/map karni hai** implementation ke waqt.
8. Production mein abhi koi deployment nahi — `retireSuperAdmin()` migration ki production-safety ki fikar abhi zaroori nahi (phir bhi harmless/safe hai, rakhi rahegi).
