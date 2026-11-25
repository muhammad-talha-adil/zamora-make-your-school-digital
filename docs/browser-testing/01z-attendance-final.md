# 01z · 01y fixes re-check (Attendance final)

Same browser chat mein paste karein. Ctrl+Shift+R. Login pehle se hai to logout/login MAT karo.

```
LOGIN: pehle check karo owner login hai ya nahi. Na ho to hi login karo: owner@school.com / 123456. Campus Gulberg. Logout/login baar baar mat karo.
FOCUS: 01y fixes ka re-check. DB reset/delete nahi. Naya data "T-z <number> ...". Staff PORTAL nahi. Window ~1014px.
Har item PASS/FAIL + ek line. Na ho sake to "NOT TESTED + wajah". FAIL par exact message/URL. 500 par exact error copy karo.

A1 Class 1, 25 Nov (Sara pehle se saved): Ahmed ko Present karke Submit. Ab save hota hai + success toast? Reload par Sara aur Ahmed dono Present?
A2 Hub > Mark Attendance, Nursery 25 Nov (Noor saved): doosra student mark + Submit. Save hota hai? Toast?
A3 Saved row ka status badal ke (Present -> Late) dobara Submit: update hota hai, duplicate nahi?
A4 Direct URL (/attendance/create?session_id=..&class_id=..&date=2026-11-25) par bhi A1 jaisa save kaam karta hai? Server validation error aaye to toast dikhe?
A5 19 Nov Class 1 (koi student nahi): message "No students enrolled in this class on this date."?
A6 Hub status dropdown: "Present"/"Absent (...)" kate nahi?
A7 Class Report: column header "Class / Section" (Campus nahi), cell mein campus nahi. /finance/transactions rows mein campus naam nahi. /finance dashboard heading ke paas campus select/label nahi.
A8 Attendance Time (check-in/out) bhara ho to dobara save par error nahi?
A9 Regression: khali Submit inline+toast, Staff attendance report, Payroll list/slip net ek jaise.
A10 Baqi list pages par Campus filter/column ek nazar (Exams, Staff, Transport, Fee, Inventory, Finance reports): sirf list do.

AAKHIR: table (A1-A10) + LOG BLOCK + DATA CREATED + CONFUSING + 2 lines: "Attendance SATISFIED/NOT + top 3", "Agla module (Exams) ke liye ready? (haan/nahi)".
```
