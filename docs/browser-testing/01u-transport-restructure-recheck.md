# 01u · Transport restructure + 01t re-check

Same browser chat mein paste karein. Ctrl+Shift+R. Login pehle se hai to logout/login MAT karo.

```
LOGIN: pehle check karo owner login hai ya nahi. Na ho to hi login karo: owner@school.com / 123456. Campus Gulberg. Logout/login baar baar mat karo.
FOCUS: naya Transport structure + 01t fixes ka re-check. DB reset/delete nahi. Naya data "T-u <number> ...". Staff PORTAL nahi.
Har item PASS/FAIL + ek line. Na ho sake to "NOT TESTED + wajah". FAIL par exact message/URL. 500 par exact error copy karo.

PART 1 - Transport
A1 Sidebar Transport ke neeche exactly 5: Dashboard, Vehicles, Routes & Stops, Expenses, Reports. Student sidebar mein "Transport Assignment" aur "Transport Monthly Dues". Sab khulte hain?
A2 /transport sirf summary cards (koi form/tab nahi)? Purana /transport?tab=routes, ?tab=assignments, ?tab=dues sahi naye page par redirect?
A3 Vehicles: Add dialog khali save -> har field ke neeche "X is required.", button disabled nahi, chhota toast, pehli galat field tak scroll. Add + Edit + search.
A4 Routes & Stops: Add route (monthly fee khali pehle), stop add. Campus dropdown nahi? Same naam isi campus mein dobara -> friendly message?
A5 Expenses: Add/Edit, khali submit message. Reports: route-wise students/dues/expenses dikhte hain?
A6 /students/transport: student chun kar route/stop assign (kitne clicks?). Route dropdown sirf header campus ke routes, duplicate naam nahi. Campus dropdown nahi.
A7 /students/transport-dues: Generate Dues. Pehle se voucher wale mahine par toast "Added Rs X transport to N existing vouchers"; dobara generate par 0 new, duplicate nahi.
A8 Student detail page par Transport card (route, stop, fee, Assign/Change)?
A9 Header campus Gulberg<->Johar: sab Transport pages naye campus ka data dikhate hain?
A10 Payment form mein transport line ka tag "Transport"?
A11 400px: Transport pages, dialogs, Student Transport pages: kuch kata nahi / horizontal scroll nahi?

PART 2 - 01t re-check
B1 Staff list: header campus switch ke baad bina manual reload naya campus ka staff? (pichli baar fail)
B2 Attendance /attendance/create: Load Students aur Reset buttons card ke andar? Future date par friendly message? Campus header se?
B3 Footer sab pages par window ke bilkul neeche (Create Exam jaisi chhoti pages par bhi)?
B4 Exams Create: khali submit par inline "X is required.", button disabled nahi, form poori width?
B5 Owner ke liye /settings/menu-settings khulta hai, toggle kaam karta hai?
B6 Session add: Start/End Year dropdown bhare hue, naam "2026-2027" jaisa, overlap par clashing session ka naam?
B7 Designations page 2 (pagination) sahi?
B8 Mobile (400px) sidebar drawer mein labels dikhte hain?

PART 3 - NOT TESTED (QA-PLAN Deferred se)
C1 Receipt Print dialog (school naam, items, balance). C2 Payroll replace confirm. C3 Designation in-use delete message. C4 Logo upload, password change errors, Subscription save.

AAKHIR: table (A1-A11, B1-B8, C1-C4) + LOG BLOCK + DATA CREATED + CONFUSING + 3 lines: "Transport SATISFIED/NOT + top 3", "Staff/Attendance SATISFIED/NOT", "Settings SATISFIED/NOT + top 3".
Context khatam hone wala ho to pehle ab tak ke tables do.
```
