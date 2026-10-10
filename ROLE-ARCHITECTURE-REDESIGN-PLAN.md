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

**Naya:** Owner header mein ek campus-dropdown — jis campus pe click kare, poora admin panel sirf usi campus ka data dikhaye (jab tak dropdown se "All Campuses" na choose kare).

**Implementation (session-based, minimal):**
- Naya column/session key: `viewing_campus_id` (session mein store, cookie jaisa `sidebar_state` already hota hai).
- `app/Http/Controllers/Concerns/ScopesCampusForUser.php::resolveCampusId()` — owner/developer ke case mein ab `session('viewing_campus_id')` ko priority dena (agar set hai), warna purana `null` behavior.
- `app/Policies/Concerns/ChecksSchoolReach.php::reaches()` — same treatment taake policies bhi switcher ko respect karen.
- `HandleInertiaRequests` shared props mein `campuses` list + `activeCampusId` add karna (header dropdown ke liye).
- Naya chhota route: `POST /settings/switch-campus` → session set → redirect back.
- Frontend: `AppShell.vue`/header mein naya `CampusSwitcher.vue` dropdown component (sirf owner/developer role ke liye conditionally render).
- **Campus Admin ko ye dropdown bilkul nahi dikhega** — unka `resolveCampusId()` pehle se hi unke apne campus pe force hai (`isCampusRestricted()` branch), switcher logic unhe touch hi nahi karega.

---

## 3. Har campus ka sirf 1 Campus Admin

**Naya constraint:** Jab koi staff member ko `campus_admin` role diya jaye, system check kare ke us campus ka koi aur active `campus_admin` to nahi (agar hai, to purane se role hata kar naye ko do, ya error de kar user se confirm karwaye).

**Implementation:**
- Jahan role assignment hota hai (Staff role-assign request/controller), ek validation rule: `campus_admin` role + campus pe already koi aur user ye role rakhta hai → ya to block karo (error: "Is campus ka admin pehle se X hai") ya automatically demote karo with confirmation dialog.
- Recommended: **block + suggest replace** (safer, non-tech-admin ko samajh aaye) — ek confirm dialog: "Ye campus already [Name] manage kar raha hai. Unhe hata kar [New] ko admin banayein?"

---

## 4. Campus Admin: staff ke sub-roles banata hai

**Abhi:** Roles (teacher, head_teacher, accountant, clerk, driver, receptionist, maid) already seeded hain, aur `campus_admin` ke paas already `users.role.assign` permission hai, apne campus tak scoped.

**Change:** Koi naya backend kaam nahi — sirf confirm karna hai ke Staff Create/Edit page pe role-dropdown sirf ye operational roles dikhaye (campus_admin/developer/owner khud is list mein na ho, taake ek campus_admin galti se doosra campus_admin ya khud jaisa powerful user na bana de). Chhota frontend filter.

---

## 5. Naya: Per-staff-member Permission Page (sabse bada naya feature)

Ye client ki demand ka core hai: admin ek staff member select kare, system ki **har** permission (add student, edit student, print voucher, inventory add, etc.) ek checklist + search-bar ke sath dikhe, admin jo chahe tick kar sake — role se **independent**, aur uske mutabiq sidebar/pages sirf wahi dikhen jo allowed hain.

### Backend
- spatie/laravel-permission already direct per-user permission assignment support karta hai (`$user->givePermissionTo()`/`syncPermissions()`) — abhi sirf roles ke zariye use ho raha hai, production mein koi UI nahi.
- Naya endpoint: `GET /staff/{staffProfile}/permissions` (list all permissions grouped by module, + currently-granted ones) aur `PUT /staff/{staffProfile}/permissions` (sync direct permissions).
- Policy/Gate: sirf `campus_admin` (apne campus ke staff ke liye) + developer/owner.
- Permission list grouping: naming convention already `module.action` hai (e.g. `fee.voucher.delete`, `exam.marks.enter`) — module prefix se group banana trivial hai (`explode('.', $name)[0]`).

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

| Area | Change |
|---|---|
| `User::isSuperAdmin()` | 1 line: `super_admin` hatana |
| `RolesSeeder` | `super_admin` role definition hatana + data-migration command existing holders ke liye |
| `ScopesCampusForUser` / `ChecksSchoolReach` | session-based `viewing_campus_id` override add karna (owner/developer only) |
| Header/AppShell | naya `CampusSwitcher.vue` + route |
| Role-assignment validation | 1-admin-per-campus check |
| Staff role dropdown | admin-level roles list se chhupana |
| **Naya:** Staff Permissions page | backend endpoint + frontend page (sabse bada kaam) |
| `menus` table + seeder | naya `permission` column, existing rows tag karna |
| `HandleInertiaRequests` menu filter | role OR permission check |
| Portal | **koi change nahi** |
| Developer role | **koi change nahi** |

**Effort ka andaza (bade se chhote):** Staff Permissions page (naya feature, sabse zyada kaam) > Campus switcher (medium) > Menu permission-tagging (medium, bohot saare menu rows tag karne honge) > super_admin removal (chhota) > 1-admin-per-campus (chhota).

**Suggested build order:** (1) super_admin removal + data migration → (2) campus switcher → (3) 1-admin-per-campus guard → (4) Staff Permissions backend+page → (5) menu permission column + tagging.
