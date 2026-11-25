# 01y · 01x fixes re-check (Attendance + Campus cleanup)

Same browser chat mein paste karein. Ctrl+Shift+R. Login pehle se hai to logout/login MAT karo.

```
LOGIN: pehle check karo owner login hai ya nahi. Na ho to hi login karo: owner@school.com / 123456. Campus Gulberg. Logout/login baar baar mat karo.
FOCUS: 01x fixes ka re-check. DB reset/delete nahi. Naya data "T-y <number> ...". Staff PORTAL nahi. Window ~1014px, 400px sirf PART 2 mein.
Har item PASS/FAIL + ek line. Na ho sake to "NOT TESTED + wajah". FAIL par exact message/URL. 500 par exact error copy karo.

PART 1 - Attendance
A1 Create page, Class 1: sirf ek student Present, baqi "Select" chhodo, Submit. Ab save hota hai? Reload par wo student Present, baqi Select? Form ke neeche hint "Students left on Select will not be saved."?
A2 Kuch bhi mark kiye bina Submit: inline "Mark at least one student." + chhota toast. Ye create page AUR Hub > Mark Attendance tab dono par.
A3 Hub > Mark Attendance mein partial mark (ek student) save hota hai?
A4 Direct URL (/attendance/create?session_id=..&class_id=..) kholo: console mein "Invalid prop" warn nahi? Submit kaam karta hai?
A5 Class Report: T Seventeen ka Class/Section attendance form (Class 1 - A) se match karta hai? Aur baqi students ka bhi?
A6 Class Report 1014px par: Reg No/Campus/Class/Guardian columns chhupe hain (2xl se chhote), table cramped nahi, Late %, Late Days, Print theek.

PART 2 - Responsive 400px
B1 400px: class report, attendance create, hub, payroll, slip, staff report: page horizontal overflow nahi.

PART 3 - Campus cleanup (header decide karta ho to koi Campus dropdown/column/label nahi)
C1 /fee/payments, /fee/vouchers, /fee/structures: "All Campuses" / Campus column / "Campus: ..." ab nahi?
C2 /inventory/items-stock (saari tables), /finance/transactions, /students (cards): campus ka text/filter nahi?
C3 Header ko Johar karo: in sab pages par sirf Johar ka data aata hai? Phir wapas Gulberg.
C4 Aur koi list page jahan Campus filter/column bacha ho (Exams, Staff, Transport, Finance dashboard/reports, Library agar hai) - sirf list do, fix nahi.

PART 4 - Regression
D1 Payroll run: list/slip/Release amount ab bhi ek jaise? Reject leave dialog (agar pending leave ho; na ho to naya "T-y 1" leave bana ke) khali submit: inline + toast, bada modal nahi.

AAKHIR: table (A1-A6, B1, C1-C4, D1) + LOG BLOCK + DATA CREATED + CONFUSING + 3 lines: "Attendance SATISFIED/NOT + top 3", "Campus cleanup SATISFIED/NOT + list", "Agla module (Exams) ke liye ready? (haan/nahi)".
Context khatam hone wala ho to pehle ab tak ke tables do.
```
