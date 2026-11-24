# 01r · Fee re-check (01q fixes) + Staff module start

Same browser chat mein paste karein. Ctrl+Shift+R. Login pehle se hai to logout/login MAT karo.

```
LOGIN: pehle check karo owner login hai ya nahi. Na ho to hi login karo: owner@school.com / 123456. Campus Gulberg. Logout/login baar baar mat karo.
FOCUS: PART 1 Fee (sirf dobara check), PART 2 Staff admin side (Staff PORTAL / staff ka apna login bilkul nahi dekhna). DB reset/delete nahi. Naya data "T-r <number> ...". MASTER PROMPT P1-P10.
Har item PASS/FAIL + ek line. Na ho sake to "NOT TESTED + wajah". FAIL par exact message/number. Phone check = 400px, resize ke baad width confirm karo.

PART 1 - Fee (01q ke fixes)
R1 Payment Methods report: Total aur % sahi (RsNaN/0% nahi)? Cash+Bank+Cheque = Collection report total?
R2 Reversed receipt: Remaining Balance asli (9,000 wala case), Paid sahi, REVERSED badge?
R3 Receipt print + Print dialog: late fee alag line (voucher row nahi), Fee Items mein sirf asli allocated amount (1,000 paid to 1,000, 10,000 nahi)? School naam, balance, concession dikhte hain?
R4 Vouchers list: header "select all" dabao -> saari rows tick, Print Selected ginti sahi, dobara dabao -> sab untick?
R5 Campus auto: Transport Route/Vehicle/etc, Student Create, Fine Rules, Receive Payment, Voucher Generate, Inventory forms: campus pehle se header campus (ya read-only)? Header Johar karke 2 forms: Johar default? Save button par double click: sirf 1 record bana?
R6 Campus access: header Johar par Gulberg ke student ka Ledger/Profile URL kholo: 403/404 aaye? Header wapas Gulberg: wahi student khule?
R7 Voucher Generate auto defaults: current month pehle se tick, class chunne par section auto (agar 1 hi ho), Academic Session auto (active)?
R8 Phone display: form, list, profile, receipt sab jagah ek jaisa 0333-1112233? Purane students ke numbers bhi?
R9 Transport due: jis mahine ka voucher pehle se ban chuka, uska Transport charge usi voucher mein line ban kar juda? Dobara generate/save par duplicate line nahi?
R10 Inventory due: agar `qa:seed-inventory` ka data hai to student ko item charge -> voucher mein inventory line, payment form mein "Inventory" tag, total sahi. Data na ho to NOT TESTED + kya missing. Inventory dashboard par "$" ki jagah Rs? Console mein "Failed to resolve component: Icon" nahi?
R11 Fee dashboard: sidebar se khulta hai, /fee redirect, total Outstanding report ke barabar, 500 nahi?
R12 Poora flow naya student "T-r Final": admit -> voucher -> late fee+concession partial -> baaqi -> receipt -> reverse -> Ledger/Outstanding/Defaulters/Profile ek hi number?

PART 2 - Staff module (admin side). Maqsad: functionality theek, kam se kam clicks, koi error nahi, hisaab/data sahi, har warning/error user-friendly.
S1 Staff list: search, filters (department/designation/status/campus), pagination, sort. Khali state par friendly message? Kisi bhi action par 500/console error?
S2 Staff Create: fresh form par koi laal message nahi; campus header se auto; phone auto-format (0333-1112233); CNIC auto-format (dash khud); email/CNIC duplicate par friendly message (technical error nahi); required fields ke neeche saaf message; negative number type nahi hota (salary, allowances); salary 0 allowed, bohot bada (1 crore+) par message. Kam se kam kitne clicks mein naya staff bana? Count likho.
S3 Staff Create ke baad: redirect kahan? Success popup? Naya staff list/profile mein sahi data ke saath? Employee/staff code auto bana, duplicate nahi?
S4 Staff Edit: purani values bhari aati hain? Save ke baad badli values sahi? Phone/CNIC wapas sahi format mein? Bina badlaav save par error nahi?
S5 Staff Profile/Show: sab tabs/cards khulte hain (personal, job, salary, documents, etc.), kuch khali/undefined/null/NaN to nahi dikhta? Phone/dates/amounts sahi format?
S6 Salary / allowances / deductions (jo bhi staff form mein hai): basic + allowances - deductions = net sahi? Edit karke dobara dekho: total update? Percent wala field 100 se bada par message?
S7 Designations / Departments / Roles / Shifts ya jo bhi Staff setup pages hain: create, edit, delete. Khali submit par field ke neeche message? Duplicate naam par friendly message? Jis designation/department mein staff ho usay delete karne par samajh aane wala message (500 nahi)?
S8 Staff status badlo (active/inactive/resigned etc. jo hai): kya asar parta hai (list filter, counts)? Dashboard/list ke counts asli ginti ke barabar?
S9 Documents / photo upload (agar hai): galat file type ya bahut bari file par friendly message? Upload ke baad dikhta hai, delete kaam karta hai?
S10 Date fields (joining date, DOB): future joining date, DOB aaj se baad, joining DOB se pehle: friendly message? Age/experience hisaab sahi?
S11 Staff login on create (agar option hai): user/role ban jata hai, password sahi, duplicate email par friendly message. (Sirf bana kar dekho, Staff portal mat kholo.)
S12 Campus: header Johar karo: staff list sirf Johar ka? Gulberg staff ka URL kholo: 403/404? Create mein Johar default?
S13 Permission/role: koi aur role ka login nahi chahiye; sirf owner se dekho ke menu mein Staff ke sab links khulte hain, koi 404/500 nahi.
S14 Phone 400px: Staff list, Create, Edit, Show: kuch kata nahi, buttons wrap, table scroll/stack, bahut khali jagah nahi?
S15 Har Staff form par: "-" type nahi hota, placeholder hai, Enter dabane par double submit nahi, Cancel/Back kaam karta hai?
S16 Staff Payroll/Salary ka page agar hai (generate, payslip, deductions): ek mahina generate karo (T-r staff par), gross/net sahi, dobara generate par duplicate nahi, payslip print khulti hai. Na ho to NOT TESTED + kya hai/kya nahi.
S17 Poora flow ek naya staff "T-r Teacher": create (kam clicks) -> profile dekho -> edit -> salary set -> status badlo -> list mein dhundo. Sab sahi, koi error nahi?
S18 Console/500: sab Staff pages par koi 500 ya console error?

AAKHIR: table (R1-R12, S1-S18) + LOG BLOCK + DATA CREATED + CONFUSING (jahan user ko type/click zyada karna pada, ya message samajh nahi aaya) + 2 lines: "Fee module FINAL SATISFIED / NOT SATISFIED, kyun" aur "Staff admin side: kitna kaam kar raha hai, top 5 masle" (NOT ho to top 3 Fee ke).
Context khatam hone wala ho to pehle ab tak ke tables do.
```
