# 01s · Fee + Staff re-check (01r fixes) + Settings

Same browser chat mein paste karein. Ctrl+Shift+R. Login pehle se hai to logout/login MAT karo.

```
LOGIN: pehle check karo owner login hai ya nahi. Na ho to hi login karo: owner@school.com / 123456. Campus Gulberg. Logout/login baar baar mat karo.
FOCUS: PART 1 Fee re-check, PART 2 Staff admin re-check (Staff PORTAL nahi), PART 3 Settings. DB reset/delete nahi. Naya data "T-s <number> ...". MASTER PROMPT P1-P10.
Har item PASS/FAIL + ek line. Na ho sake to "NOT TESTED + wajah". FAIL par exact message/URL/number. Phone check = 400px, resize ke baad width confirm karo. 500 page par ab exact error dikhta hai: FAIL par wo message copy karo.

PART 1 - Fee
A1 /fee/dashboard Gulberg aur Johar dono par 200, 500 nahi. Total Outstanding = Outstanding report?
A2 Transport Generate Dues: toast "Added Rs X transport to N existing vouchers, created M new dues, skipped K". Voucher detail par note. Dobara generate par duplicate nahi?
A3 Transport route ka same naam (isi campus) dobara: naam ke neeche friendly message? Dusre campus mein same naam allowed?
A4 Payroll Generate: campus default header campus (All Campuses nahi)?
A5 Inventory due (`php artisan qa:seed-inventory` ka data): student ko item charge -> voucher mein inventory line, payment form mein "Inventory" tag, total sahi. Data na dikhe to NOT TESTED + kya missing.
A6 Receipt "Print" dialog: school naam, items, balance, concession print preview mein?

PART 2 - Staff admin (URL ab /staff, /staff/{id}; purane /staff/people/... redirect hone chahiye)
B1 /staff/people/1 purana URL -> /staff/1 par redirect? Sidebar ke saare Staff links khulte hain, 500 nahi?
B2 Create: fresh form par laal message nahi; * required marks; campus header se; phone auto-format; CNIC "3520212345671" type karo -> khud 35202-1234567-1; galat CNIC par friendly message. Khali Save: har field ke neeche message, button disabled nahi.
B3 Salary 20000000 -> message (max 1 crore); negative type nahi hota; 0 allowed. Hire date future / DOB se pehle -> message. DOB aaj ya baad -> message.
B4 Duplicate CNIC aur phone (T-r Dup wala): message mein existing staff ka naam aata hai?
B5 Edit/Show: DOB aur Hire Date bhari hui dikhti hain (khali nahi)? Bina badlaav save par error nahi?
B6 Status toggle: sirf simple confirm, password nahi? List filters/counts theek?
B7 Campus: header Johar -> sirf Johar staff; Gulberg staff ka /staff/{id} 403; Create mein Johar default. Wapas Gulberg.
B8 Staff list: Position (designation) filter, khali state friendly, search, pagination.
B9 Designations: khali naam par "Name is required." inline; duplicate par friendly; unused delete ho jaye; jis designation par staff ho uska delete friendly message (500 nahi).
B10 Payroll: pehle se bane run ko dobara generate -> confirm "This will replace the existing run for <month> <year>"; cancel par kuch nahi badla; paid items bache rahein. Slip par staff ka asli campus (School-wide sirf jab campus na ho). Basic + Allowance - Deduction = Net.
B11 Staff dashboard: Monthly Salary Cost = Payroll gross? "Recently Joined" mein naye staff?
B12 Phone 400px: Staff list, Create, Show, Payroll: kuch kata nahi?
B13 Poora flow naya "T-s Teacher": create (clicks gino) -> profile -> edit -> salary -> status -> list mein dhundo.

PART 3 - Settings (har page: khulta hai? 500/console error? khali submit par inline message, negative nahi, duplicate par friendly message, placeholders, phone/campus auto, 400px)
C1 /settings/school-hub aur school-profile (naam, phone auto-format, save, logo).
C2 /settings/campuses + campus-types: create/edit/delete, duplicate naam, jis campus mein data ho uska delete friendly message.
C3 /settings/school-classes, sections, subjects, class-subjects: create/edit/delete, duplicate, in-use delete message, class chunne par section/subject auto.
C4 /settings/sessions, months, exam-types: active session ek hi, overlapping dates, future/past date rules.
C5 /settings/menu-settings, appearance, activity-log: kaam karte hain? Menu toggle ka asar sidebar par?
C6 /settings/profile, password, two-factor, subscription: profile save, password change friendly errors.
C7 Console/500: sab Settings pages par?

AAKHIR: table (A1-A6, B1-B13, C1-C7) + LOG BLOCK + DATA CREATED + CONFUSING (jahan zyada type/click karna pada ya message samajh nahi aaya) + 3 lines: "Fee FINAL SATISFIED/NOT", "Staff admin SATISFIED/NOT + top 3 masle", "Settings: kitna kaam kar raha, top 5 masle".
Context khatam hone wala ho to pehle ab tak ke tables do.
```
