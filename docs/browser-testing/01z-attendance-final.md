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

PART 2 - Exams (pehli nazar, naya module; fix nahi, sirf report)
E1 Sidebar Exams ke saare submenus kholo: koi 500/404/console error? List do.
E2 Exams list: columns, "#" aur "Actions" header, Campus filter/column nahi, empty state friendly?
E3 Naya exam/term banao ("T-z Exam 1"): khali submit par inline "X is required." + chhota toast (bada modal nahi), dialog data na udey. Phir sahi bharo: kitne clicks? Fields auto-fill (session/class)?
E4 Exam ke andar subjects/schedule/date add karo: khali submit, duplicate, past date ka behaviour.
E5 Marks entry (Class 1, ek subject): sab students aate hain? Max marks se zyada number par friendly error? Save ke baad reload par marks wahi?
E6 Result/grade/report card/print: numbers (total, %, grade) sahi? 1014px aur 400px par overflow?
E7 Header Johar: sirf Johar ka exam data; wapas Gulberg.
E8 Confusing/missing: kya aisa kuch hai jo school owner ko chahiye (admit card, date sheet, result print)?

AAKHIR: table (A1-A10, E1-E8) + LOG BLOCK + DATA CREATED + CONFUSING + 2 lines: "Attendance SATISFIED/NOT + top 3", "Exams SATISFIED/NOT + top 3".
```
