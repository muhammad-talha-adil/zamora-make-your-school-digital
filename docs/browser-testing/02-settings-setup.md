# 02 · Settings and school setup (owner, then principal@ for campus scope)

```
MASTER PROMPT (jo is chat mein pehle paste ho chuka) ke rules + SPC har page par lagao. Login: owner@school.com, phir principal@school.com, phir developer@web.com.

PAGES: Settings > School Profile, School Setting hub (/settings/school-hub), Campuses, Campus Types, Classes, Sections,
Subjects, Academic Sessions, Exam Types, Attendance Settings, har module ki settings, Sidebar/Menu management
(/settings/menu-settings), Appearance/Theme, Activity Log, Profile/Password/2FA.

SCENARIOS (har entity ke liye CRUD + edge):
1. School profile: naam/logo/address/phone badlo, logo upload (jpg/png/bohot bara/galat type), save, refresh, header/print mein dikhe.
2. Campus: naya campus banao (sirf required), (sab fields), duplicate naam/code, edit, deactivate/reactivate, active campus wale students ke
   saath deactivate, delete (blocked kyun?), campus type badalna. Campus ke baghair class/section/student banane ki koshish -> saaf message?
3. Classes: single create, BULK create (naya feature: multiple classes ek saath), duplicate, ordering, edit, delete jab students/sections hon,
   campus scoping (campus admin ko sirf apne campus ki classes?).
4. Sections: single + bulk create, capacity, class ke andar duplicate, delete jab students hon.
5. Subjects: single + bulk, class-subject mapping, campus scoping, compulsory/optional, delete jab marks/timetable mein use ho.
6. Sessions: naya session, overlapping dates, active session switch (kya kya badalta hai?), campus-wise sessions, close session, "No active
   session" banner behaviour.
7. Exam types, grade scales, attendance settings (late rule, fines, auto-lock, working days), fee-related settings: save, validation, effect
   dosre module mein nazar aata hai?
8. Menu management: item show/hide, reorder (drag), role/permission assign, rename; effect sidebar mein foran? role-wise check.
9. Appearance: har palette, light/dark, font; sab pages par apply? refresh par bacha?
10. Activity log: filter by user/module/date, entries asli actions se match karte hain, owner/developer ke ilawa koi dekh sakta hai?
11. Permissions: principal@ aur dosray roles ko in pages ka kitna access? Jo nahi hona chahiye wahan URL se 403?
12. Empty-state: naya campus banane par har list ka "kuch nahi" message helpful hai aur next-step button dikhata hai?

EFFICIENCY: naya school setup (campus -> session -> class -> section -> subject) kitne clicks/pages? Kya guided setup/wizard ya sahi
order ka hint chahiye? Bulk forms ko sahi dekho (UX, errors per-row, preview).
```
