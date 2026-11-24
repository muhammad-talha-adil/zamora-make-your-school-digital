# 01v · 01u fixes re-check + Attendance (student + staff) poora

Same browser chat mein paste karein. Ctrl+Shift+R. Login pehle se hai to logout/login MAT karo.

```
LOGIN: pehle check karo owner login hai ya nahi. Na ho to hi login karo: owner@school.com / 123456. Campus Gulberg. Logout/login baar baar mat karo.
FOCUS: 01u fixes ka chhota re-check, phir Attendance module poora. DB reset/delete nahi. Naya data "T-v <number> ...". Staff PORTAL nahi. Window ~1014px par test karo, 400px sirf PART 4 mein.
Har item PASS/FAIL + ek line. Na ho sake to "NOT TESTED + wajah". FAIL par exact message/URL. 500 par exact error copy karo.

PART 1 - 01u re-check (chhota)
A1 Routes & Stops: Add Route dialog mein naam+fee bhar ke "Add stop" dabao: dialog khula rahe, naam/fee na udein, naya stop khud select ho? Phir route save.
A2 /students/transport: Student dropdown sirf header campus ke students? Route dropdown mein duplicate jaisi labels nahi (vehicle/fee ke saath alag pehchan)?
A3 Transport Monthly Dues Generate: skip hone par wajah likhi aati hai? Naya assignment (is mahine) ka due ban jata hai ya saaf wajah milti hai?
A4 Payroll generate form: Campus dropdown nahi (ya sirf zaroori)? Session/Menu form khali submit par chhota toast + inline (bada modal nahi)?

PART 2 - Student attendance
B1 /attendance/hub, /dashboard, /create, /class/report, /settings, /scan: sab khulte hain, 500/console error nahi? Hub par Present/Absent counts sahi?
B2 /attendance/create: Campus field NAHI hona chahiye (header se). Class chuno -> Load. Future date (aaj se aage) type karo -> inline friendly message, Load nahi hota? Submit se bhi save nahi hota?
B3 Mark All Present -> statuses sach mein "Present" dikhte hain (Select nahi)? Ek-do ko Absent/Late/Leave karo, save, toast. Kitne clicks (ginti likho)?
B4 Wahi class wahi date dobara mark: duplicate nahi banta, purani attendance edit ke taur par load hoti hai?
B5 Sunday ya holiday par mark: friendly message? (Settings > Holidays mein ek holiday banao "T-v Holiday", us date par try.)
B6 /attendance/class/report: filters (class, date range), numbers B3 se match, print/export (agar hai), student ka naam detail par.
B7 Leave: /student-leaves/pending (approve/reject), approved leave wali date par attendance mein "Leave" auto?
B8 /attendance/scan: khulta hai, QR/barcode input ka friendly state (camera nahi to message)?
B9 Header Johar: sirf Johar ki classes/students; wapas Gulberg.

PART 3 - Staff attendance
C1 /staff/attendance (+statuses): sab staff list, mark all present, late/leave, save, dobara mark par duplicate nahi.
C2 Future date, holiday, inactive staff: friendly message.
C3 Staff attendance report/month view: counts sahi; payroll se link (sirf dekho, payroll test agla round).

PART 4 - Responsive/UX
D1 400px: attendance create, hub, report, staff attendance: horizontal overflow nahi, buttons card ke andar, table cramped nahi.
D2 Har form: khali submit par inline "X is required.", button disabled nahi, fresh form par laal message nahi, placeholders hain.
D3 Duplicate "Campus" ya redundant filters kahin bache hain? (jahan header decide karta ho)

AAKHIR: table (A1-A4, B1-B9, C1-C3, D1-D3) + LOG BLOCK + DATA CREATED + CONFUSING + 3 lines: "01u fixes SATISFIED/NOT", "Student attendance SATISFIED/NOT + top 3", "Staff attendance SATISFIED/NOT + top 3".
Context khatam hone wala ho to pehle ab tak ke tables do.
```
