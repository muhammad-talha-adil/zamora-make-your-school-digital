# 01w · 01v fixes re-check + Staff Payroll

Same browser chat mein paste karein. Ctrl+Shift+R. Login pehle se hai to logout/login MAT karo.

```
LOGIN: pehle check karo owner login hai ya nahi. Na ho to hi login karo: owner@school.com / 123456. Campus Gulberg. Logout/login baar baar mat karo.
FOCUS: 01v fixes ka re-check, phir Staff Payroll poora. DB reset/delete nahi. Naya data "T-w <number> ...". Staff PORTAL nahi. Window ~1014px par test karo, 400px sirf PART 4 mein.
Har item PASS/FAIL + ek line. Na ho sake to "NOT TESTED + wajah". FAIL par exact message/URL. 500 par exact error copy karo.

PART 1 - 01v re-check
A1 /attendance/class/report: ab crash nahi? Class + date range filter, numbers, student ke naam, print/export (agar hai).
A2 Hub > Leave > New Application: khali submit par chhota toast + inline "Leave type is required." (bada modal nahi), dialog khula rahe, bhara data na udey. Phir sahi bhar ke submit: leave ban jaye.
A3 /student-leaves/pending: approve aur reject kaam karte hain? Approved leave wali date par attendance mein "Leave" auto aata hai?
A4 /attendance/scan: camera na ho to friendly message (kala box nahi) + manual code input; code daalne par student mil jaye?
A5 /staff/attendance: Campus column nahi. Late/Leave mark karke save, page reload: saved status wapas wahi dikhe (Present nahi)? Dobara save par duplicate nahi.
A6 Staff attendance: future date, holiday (Settings > Holidays mein "T-w Holiday"), friendly message?
A7 Naya Staff Attendance Report (/staff/attendance/report, sidebar mein bhi?): month chuno, Present/Absent/Late/Leave/Not marked counts A5 se match?
A8 Create/Hub attendance: holiday ya future date ke baad purana banner nahi rehta? Banner selected date ke hisaab se?
A9 Transport Assignment route dropdown: labels alag pehchan wale ("Naam – Vehicle • Rs fee")?
A10 Hub tabs 400px par kat te nahi (andar scroll)?

PART 2 - Staff Payroll
B1 Sidebar Staff > Payroll (aur related submenus) sab khulte hain, 500/console error nahi?
B2 Payroll Generate: Campus field NAHI. Month khali submit -> inline "Month is required." + chhota toast. Month chuno -> generate; kitne clicks?
B3 Generate ke baad list: har staff ki salary, allowances/deductions, net. Numbers staff ki base salary se match? Late/absent ka asar (agar rule hai) sahi?
B4 Dobara same month generate: replace confirm friendly? Duplicate payroll nahi banta?
B5 Salary slip / payslip: view/print, school ka naam, items, net. Dialog ke andar data poora?
B6 Give Advance: khali submit inline + toast (bada modal nahi). Advance ka asar payroll ke deduction mein?
B7 Payroll mark Paid / status change: kaam karta hai, confirm friendly, dobara paid nahi hota?
B8 Staff profile > salary/payroll section: history dikhti hai?
B9 Header Johar: sirf Johar ka staff/payroll; wapas Gulberg.

PART 3 - Responsive/UX
C1 400px: Payroll list, generate, slip, staff attendance report: horizontal overflow nahi, buttons card ke andar, table cramped nahi.
C2 Har form: khali submit par inline "X is required.", button disabled nahi, fresh form par laal message nahi, placeholders hain, bada Error modal kahin nahi.
C3 Redundant "Campus" filter/column kahin bacha hai (jahan header decide karta ho)?
C4 Tables mein # column, Actions header?

AAKHIR: table (A1-A10, B1-B9, C1-C4) + LOG BLOCK + DATA CREATED + CONFUSING + 3 lines: "01v fixes SATISFIED/NOT + top 3", "Staff attendance SATISFIED/NOT", "Payroll SATISFIED/NOT + top 3".
Context khatam hone wala ho to pehle ab tak ke tables do.
```
