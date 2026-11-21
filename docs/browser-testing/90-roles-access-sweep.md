# 01 · Shell, login, roles and access (owner first)

```
MASTER PROMPT (jo is chat mein pehle paste ho chuka) ke rules + SPC har page par lagao.

A. LOGIN PAGE (logged out)
- /login: layout, branding, labels, show-password, Enter submit, Tab order, remember me, forgot-password link.
- Galat email, galat password, khali fields, spaces, uppercase email, 6+ galat koshishein (rate-limit message saaf hai?).
- 2FA/password-reset/registration pages: khulte hain? chalte hain? band hain to kya dikhta hai?
- Logged-in user /login kholay to kahan jata hai? Logout ke baad Back button par private page nazar na aaye.
- Public landing page (agar hai): links, responsive, toggle (PublicWebsiteToggle).

B. OWNER (owner@school.com) — SHELL
- Login ke baad landing page. Dashboard ka har card/number/chart sahi hai? har card click par sahi jagah jata hai?
- Dashboard ke fake/hardcoded trend badges (+2.1% wagera) mojood hain? (known issue) Ab seeded data par asli numbers?
- Sidebar: har group expand/collapse, active highlight, icon + naam consistent, collapse state refresh par yaad?
- Top bar: search, notifications, profile menu, theme toggle, campus/session switcher (agar hai), logout.
- Breadcrumbs har module mein. Browser Back/Forward. Refresh. Direct URL paste.
- Settings > Profile, Password change, 2FA, Appearance (theme/palette badalna, refresh par bacha rahe?).
- Error pages: /nonexistent (404), forbidden page, 419 expired session (session cookie hata kar form submit). Friendly text? model/file names leak to nahi?

C. HAR ROLE — login karo, logout karo, agla (har login ka natija LOG BLOCK mein: ok/blocked + landing page)
Logins: developer@web.com, principal@school.com (campus admin), headteacher@school.com, teacher1@school.com,
accounts@school.com, reception@school.com, clerk@school.com, driver1@school.com, maid@school.com,
student.test@school.com, guardian.test@school.com. (admin@ aur admin2@ exist nahi karte, skip.)
Har role ke liye:
1. Login ke baad kahan land karta hai? (developer ka /portal 404 — I-02 dobara dekho.)
2. Sidebar mein kaun se groups/items dikhte hain (list likho).
3. Har sidebar item kholo: khulta hai? khali/error to nahi? data sirf unka hai jo unhein dekhna chahiye?
4. URL probe (sirf dekho): /dashboard /students /fee/vouchers /finance /staff /settings/school-hub /transport
   /inventory /exams /attendance /timetable /portal /staff/me /staff/teaching — har ek par 200 ya saaf 403?
   (500, blank, ya "Student not found" jaisa leak = BUG.)
5. Is role ko jo nahi karna chahiye uska button/menu hidden hai ya sirf click par error?

D. TEEN WIDTH ACCESS (data scope) — sabse ahem
- School-wide (owner/developer): saare 4 campuses ka data dikhe.
- Campus-wise (principal@, accounts@, clerk@, reception@): sirf apne campus ke students/staff/fees/vouchers. Doosre campus ki record
  URL (id badal kar) kholne par 403/404, data leak nahi.
- Teacher (teacher1@, headteacher@): sirf apni assigned classes ki attendance/marks/timetable.
- Student/guardian: sirf apna/apne bachchon ka data. Doosre student ki URL (id badal kar) forbidden.
- Teeno ke liye list pages ka count, filter dropdown (kya doosre campus ke options dikhte hain?), search results, export, dashboard totals.

E. MENU/NAV CONSISTENCY
- Har role ke sidebar ke naam/icon/order owner se match karte hain? Koi dead link, duplicate item, galat group?
- Portal roles (student/guardian) ko admin sidebar ka koi hissa nahi dikhna.

F. EFFICIENCY: login se apne rozana kaam tak kitne clicks (teacher: attendance lagana, accountant: fee lena, receptionist: enquiry)?
```
