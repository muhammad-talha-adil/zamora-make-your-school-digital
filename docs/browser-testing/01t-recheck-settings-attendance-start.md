# 01t · 01s re-check (Fee/Staff/Settings) + NOT TESTED items

Same browser chat mein paste karein. Ctrl+Shift+R. Login pehle se hai to logout/login MAT karo.

```
LOGIN: pehle check karo owner login hai ya nahi. Na ho to hi login karo: owner@school.com / 123456. Campus Gulberg. Logout/login baar baar mat karo.
FOCUS: sirf 01s ke fixes ka re-check + jo NOT TESTED tha. DB reset/delete nahi. Naya data "T-t <number> ...". Staff PORTAL nahi. MASTER PROMPT P1-P10.
Har item PASS/FAIL + ek line. Na ho sake to "NOT TESTED + wajah". FAIL par exact message/URL/number. 500 par exact error copy karo.

PART 1 - Fee
A1 /fee/dashboard Gulberg vs Johar: numbers alag (campus-scoped)? "Total Outstanding" card = Outstanding report total? Overdue = Defaulters? Recent Payments dates readable (2026-11-24 jaisi ISO nahi)?
A2 Transport Generate Dues: pehle se bane voucher wale mahine par toast "added to N existing vouchers"; voucher detail par line/note; dobara generate par duplicate nahi.
A3 Receipt "Print" dialog: school naam, items, balance, concession preview mein? (NOT TESTED tha)
A4 Header campus switch karo (Gulberg<->Johar): page khud reload ho kar naye campus ka data dikhaye? Inventory filters aur Student Inventory default header campus?

PART 2 - Staff admin
B1 Sidebar Staff > "Dashboard" (/staff/dashboard) aur "Staff Directory" (/staff) alag pages?
B2 Staff Create: salary/hire date par browser ka native tooltip nahi aata; galat value par inline message, theek karte hi message khud ghayab?
B3 Designations: System Role dropdown nahi hai? 15 designations list mein (Principal ... Coordinator)? Naya designation banao + permission checklist se kuch tick karo, save.
B4 Naya staff "T-t Teacher" "Teacher" designation ke saath: Staff profile/permissions mein Teacher ke default permissions copy hue? Phir us user ki ek permission badlo -> designation template par asar NAHI. Designation template badlo -> pehle wale staff par asar NAHI.
B5 Designation delete jis par staff ho: friendly message (500 nahi). Unused delete ho jaye.
B6 Payroll: pehle se bane (e.g. School-wide) mahine ko Gulberg ke liye generate -> confirm "replace" aaye? Cancel par kuch nahi badla? Confirm par sirf 1 run bacha (double nahi)? Jis run mein payment ho chuki uska replace friendly refuse? Pending Payroll Payable double count nahi?
B7 Staff Dashboard "Recently Joined": Hired column mein date (na ki "-")?

PART 3 - Settings
C1 /settings/menu-settings aur /settings/subscription owner ke liye khulte hain? (Subscription mein save sirf developer — owner ko friendly message ya read-only?)
C2 Khali submit (Create Campus, Add Class, Add Subject, Add Section, Campus Type, Exam Type, Session, Month, Fine Rule, Inventory type/item): button disabled NAHI, har field ke neeche "X is required."? Fresh form par laal message nahi?
C3 Duplicate campus naam: "A campus named X already exists." inline? Campus/Class/Session jis par student/staff ho uska delete: friendly message jo batata hai kya block kar raha?
C4 Sessions Add: start date pichle session ke baad automatically, 1 saal, "Set as Active" tick nahi? Overlapping dates save par message jo clashing session ka naam bataye?
C5 Add Class: campus default header campus ya "All Campuses"? (likho jo hai)
C6 Exam pages (Exams Create/Edit, Papers Edit), Bulk class/section/subject forms, Menu modal: khali submit par button disabled to nahi? (known: ye abhi silent disable ho sakte hain, FAIL likho)
C7 School Profile: wording saaf, website-content icon fields dropdown, phone auto-format, save.
C8 Koi bhi delete/status popup fail ho to server ka reason dikhta hai ("Failed... Please try again" generic nahi)?
C9 Validation fail par bada Error modal nahi, chhota toast + pehli galat field tak scroll?
C10 Menu toggle ka asar sidebar par, logo upload, password change friendly errors, pagination (list pages): NOT TESTED the, ab test karo.
C11 Phone 400px: Settings pages, Staff Create, Fee dashboard: kuch kata nahi?

PART 4 - Attendance (sirf pehli nazar, naya module)
D1 /attendance/hub, /attendance/dashboard, /attendance/create, /attendance/class/report, /attendance/settings: sab khulte hain? 500/console error?
D2 Ek class ki attendance mark karo (kam se kam clicks, "sab present" shortcut?), save, report mein dikhe, dobara mark par duplicate nahi.
D3 Header campus Johar: sirf Johar ki classes/students?
D4 Date future par mark: friendly message?

AAKHIR: table (A1-A4, B1-B7, C1-C11, D1-D4) + LOG BLOCK + DATA CREATED + CONFUSING + 3 lines: "Fee SATISFIED/NOT", "Staff admin SATISFIED/NOT + top 3", "Settings SATISFIED/NOT + top 5".
Context khatam hone wala ho to pehle ab tak ke tables do.
```
