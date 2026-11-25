# 02b · Exams final check + Inventory first look

Same browser chat mein paste karein. Ctrl+Shift+R. Login pehle se hai to logout/login MAT karo.

```
LOGIN: pehle check karo owner login hai ya nahi. Na ho to hi login karo: owner@school.com / 123456. Campus Gulberg. Logout/login baar baar mat karo.
FOCUS: Exams chhoti fixes ka check, phir Inventory pehli nazar (fix nahi, sirf report). DB reset/delete nahi. Naya data "T-b <number> ...". Staff PORTAL nahi. Window ~1014px, 400px sirf PART 3 mein.
Har item PASS/FAIL + ek line. Na ho sake to "NOT TESTED + wajah". FAIL par exact message/URL. 500 par exact error copy karo.

PART 1 - Exams chhoti fixes
X1 Report card aur Date Sheet 420px par overflow nahi? Report card header mein "Campus" lafz dohra nahi?
X2 Date sheet / admit cards / dashboard / Papers page ke "Existing Papers" card mein time "09:00 AM" (seconds nahi), date "16 Dec 2026", Dashboard Upcoming Papers date "02 Dec 2026"?
X3 Admit Cards: Class 1 ke saare students (Ali, T Seventeen bhi, jinke marks nahi) ke card aate hain?
X4 Papers page: saved paper row par Edit + Delete; edit save hota hai; Existing Papers card narrow width par kata nahi (Marks/Time wrap ho); duplicate subject par "This subject already has a paper in this exam for this class."

PART 2 - Inventory (pehli nazar, sirf report; Timetable abhi hide hai)
I1 Sidebar Inventory ke saare submenus kholo: 500/404/console error? List do.
I2 Har list page: columns, "#" aur "Actions" header, Campus filter/column/label nahi, empty state friendly?
I3 Naya item/category/type ("T-b Item 1") banao: khali submit par inline "X is required." + chhota toast (bada modal nahi), dialog data na udey. Kitne clicks? Placeholders, auto-fill?
I4 Stock in / adjustment: quantity 0 ya negative, zyada nikalna (stock se zyada) par friendly message? Save ke baad stock sahi, history/adjustments table mein entry?
I5 Low stock / reorder level: item ka alert ya highlight? Dashboard/summary numbers sahi?
I6 Edit/delete: confirm friendly (SweetAlert nahi), used item delete par friendly refusal.
I7 Header Johar: sirf Johar ka inventory; wapas Gulberg.
I8 Search/filter/pagination, export/print agar hai.
I9 Confusing/missing: owner ko kya chahiye (issue to staff/class, purchase/vendor, stock report)?

PART 3 - 400px
Y1 400px: inventory pages, exams papers page, date sheet, report card: page horizontal overflow nahi, text kata nahi.

AAKHIR: table (X1-X4, I1-I9, Y1) + LOG BLOCK + DATA CREATED + CONFUSING + "Exams SATISFIED/NOT", "Inventory SATISFIED/NOT + top 3".
Context khatam hone wala ho to pehle ab tak ke tables do.
```
