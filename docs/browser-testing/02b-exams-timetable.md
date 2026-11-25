# 02b · Exams final check + Timetable first look

Same browser chat mein paste karein. Ctrl+Shift+R. Login pehle se hai to logout/login MAT karo.

```
LOGIN: pehle check karo owner login hai ya nahi. Na ho to hi login karo: owner@school.com / 123456. Campus Gulberg. Logout/login baar baar mat karo.
FOCUS: Exams chhoti fixes ka check, phir Timetable pehli nazar (fix nahi, sirf report). DB reset/delete nahi. Naya data "T-b <number> ...". Staff PORTAL nahi. Window ~1014px, 400px sirf PART 3 mein.
Har item PASS/FAIL + ek line. Na ho sake to "NOT TESTED + wajah". FAIL par exact message/URL. 500 par exact error copy karo.

PART 1 - Exams chhoti fixes
X1 Report card aur Date Sheet 420px par overflow nahi? Report card header mein "Campus" lafz dohra nahi?
X2 Date sheet / admit cards / dashboard mein time "09:00 AM" (seconds nahi), Dashboard Upcoming Papers date "02 Dec 2026"?
X3 Admit Cards: Class 1 ke saare students (Ali, T Seventeen bhi, jinke marks nahi) ke card aate hain?
X4 Papers page: saved paper row par Edit + Delete; edit save hota hai; duplicate subject par "This subject already has a paper in this exam for this class."

PART 2 - Timetable (naya module, sirf report)
T1 Sidebar Timetable ke saare submenus kholo: 500/404/console error? List do.
T2 Har list page: columns, "#" aur "Actions" header, Campus filter/column nahi, empty state friendly?
T3 Setup: periods/slots banao ("T-b Period 1"): khali submit par inline "X is required." + chhota toast (bada modal nahi), dialog data na udey. Kitne clicks? Auto-fill (session, times)?
T4 Class ka timetable banao (Class 1 Section A: subject + teacher + period + day): khali submit, teacher clash (same teacher same period do class mein) par friendly message, duplicate slot.
T5 Timetable view: class-wise aur teacher-wise grid saaf dikhta hai? Print button + print clean? Saturday/Sunday/holiday ka behaviour?
T6 Subject/teacher assignment: Staff ka teacher list, subjects Exams wale subjects se match?
T7 Header Johar: sirf Johar ka timetable; wapas Gulberg.
T8 Edit/delete slot: confirm friendly (SweetAlert nahi), data sahi update.
T9 Confusing/missing: owner ko kya chahiye (substitute teacher, free period, period-wise attendance link)?

PART 3 - 400px
Y1 400px: timetable grid, setup, exams date sheet, report card: page horizontal overflow nahi.

AAKHIR: table (X1-X4, T1-T9, Y1) + LOG BLOCK + DATA CREATED + CONFUSING + "Exams SATISFIED/NOT", "Timetable SATISFIED/NOT + top 3".
Context khatam hone wala ho to pehle ab tak ke tables do.
```
