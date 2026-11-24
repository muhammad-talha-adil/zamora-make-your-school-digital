# 01x · 01w fixes re-check (Attendance + Payroll)

Same browser chat mein paste karein. Ctrl+Shift+R. Login pehle se hai to logout/login MAT karo.

```
LOGIN: pehle check karo owner login hai ya nahi. Na ho to hi login karo: owner@school.com / 123456. Campus Gulberg. Logout/login baar baar mat karo.
FOCUS: 01w fixes ka re-check. DB reset/delete nahi. Naya data "T-x <number> ...". Staff PORTAL nahi. Window ~1014px, 400px sirf PART 3 mein.
Har item PASS/FAIL + ek line. Na ho sake to "NOT TESTED + wajah". FAIL par exact message/URL. 500 par exact error copy karo.

PART 1 - Attendance
A1 Approved leave wala student (Ahmed Raza, 24 Nov, Class 1 Section A) attendance create mein khud "Leave" dikhe? Save karo, reload par bhi Leave. Naya leave (T-x 1) approve karke ek aur student/date par dobara check.
A2 Class 1 + "All Sections" + Load: ab saare sections ke students aate hain?
A3 Kuch mark kiye bina Submit: inline "Mark at least one student." + chhota toast (create aur hub dono)?
A4 Class Report: Late student ka % ab 0 nahi? "Late Days" card hai? Print button kaam karta hai (print preview saaf, filters/buttons chhupe)?
A5 Hub > Attendance List: "All Campuses" dropdown/column ab nahi? Table headers "#" (SR# nahi)?
A6 Staff Attendance Report: "Not marked" mein Sunday/holiday nahi gine gaye? Counts attendance page se match?
A7 Staff Dashboard: "Present Today" / "On Leave Today" aaj ki attendance se match (Leave wale Present mein nahi)?
A8 Do holidays ek hi date par ho to banner mein dono naam?
A9 Transport Assignment route dropdown: label "Naam • Rs fee/mo – Vehicle", fee kata nahi, field poori width? Table header "Actions"?

PART 2 - Payroll
B1 Zubair Ali ki advance deduction: payroll list ka Deduction, Net, slip ka Net, Balance due, Release salary amount - sab ek jaisa (Net = Gross + Allowance - Deduction - Advance)? Run ke totals bhi?
B2 Naya advance do (T-x advance, Rs 2,000, monthly 500) khali submit pehle: Amount/Date ke neeche inline "X is required." + toast, dialog khula. Phir sahi submit.
B3 Nov dobara Generate (replace confirm): advance ki deduction naye run mein bhi lagi? Net sahi? Paid Sana ki salary bachi rahe?
B4 Release salary (kisi pending staff ki, Net amount wahi jo list mein): paid ho, slip par Paid/Balance sahi?
B5 Staff profile > naya "Payroll" tab: month, net, paid, status, slip link; link slip khulta hai?
B6 Payroll run card par campus ka naam nahi? Items table kam columns (lg se chhote par Basic/Allowance hide)?
B7 Salary slip: sab rows label left/value right aligned; print saaf.

PART 3 - Responsive/UX
C1 400px: Payroll items table ab cramped nahi? Class report, attendance create, staff report, slip: overflow nahi?
C2 Har form khali submit: inline + toast, bada Error modal kahin nahi (Give Advance, Leave, Reject, Holiday, Generate)?
C3 Koi Campus dropdown/column/label bacha jahan header decide karta ho (Students, Fee, Exams list pages bhi ek nazar)?

AAKHIR: table (A1-A9, B1-B7, C1-C3) + LOG BLOCK + DATA CREATED + CONFUSING + 3 lines: "Attendance SATISFIED/NOT + top 3", "Payroll SATISFIED/NOT + top 3", "Agla module ke liye ready? (haan/nahi)".
Context khatam hone wala ho to pehle ab tak ke tables do.
```
