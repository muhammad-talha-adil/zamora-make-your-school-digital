# 01 · Owner only: school ko ZERO se setup karna (pehla module)

DB bilkul khali hai (sirf owner@school.com ka login use hoga). Sirf OWNER se kaam karo. Browser mein MASTER PROMPT (00-START-HERE.md)
ke baad yeh paste karein. Har phase ke baad "PHASE DONE" ek line; aakhir mein LOG BLOCK.

```
Is chat ke MASTER PROMPT ke rules + SPC har page par lagao. Login sirf owner@school.com / 123456.

Tum ek asli school ke owner ho jo yeh app pehli baar khol raha hai. School ko bilkul zero se chalao jaise ek sachcha owner karega.
Data (naam, numbers, class, fees, staff, students) tum khud socho, realistic Pakistani school jaisa. Kam se kam 2 campuses rakho taake
campus-wise cheezein bhi test hon. Jo data banao uska record rakho (aakhir mein DATA CREATED mein do).

Har step par:
1. Pehle ZERO STATE dekho: khali page ka message madadgar hai? agla kadam batata hai? koi page bina data ke crash/500/blank to nahi?
2. Phir data banao: pehle sahi data, phir galat/edge data (khali, duplicate, bohot lamba, special chars, Urdu naam, negative, future/past date).
3. Phir verify karo ke wo data agle modules mein sahi dikhta hai.
4. SPC lagao (UI, responsive 390/768/1024/1440, dark/light, consistency, validation, click count).
Agar kahin atko, confusing ho, ya app ne order ka hint na diya to wohi sabse ahem finding hai.

PHASE 1 · FIRST RUN + SCHOOL
- App pehli baar: dashboard, banners (jaise "no campus/no session"), har menu group ka khali page. Kya sab theek kaam karta hai?
- School profile (naam, address, contact, logo upload: sahi/bohot bara/galat type), appearance/theme.
- Campuses banao (kam az kam 2): code/naam/type duplicate, edit, deactivate/reactivate.
- Academic session banao aur active karo (overlap, end<start, campus-wise); banner ka ghayab hona.
PHASE 2 · ACADEMIC STRUCTURE
- Classes (single aur bulk create dono), sections (single aur bulk, capacity), subjects (bulk, class-subject mapping), campus scoping.
- Exam types, grade scale, attendance settings (school timing, late rule, auto-lock), holidays, baaqi module settings.
PHASE 3 · PEOPLE
- Staff departments/designations, phir alag alag kirdaar ka staff (head teacher, teachers, accountant, clerk, driver...) alag campuses mein.
  Har ka login kaise banta hai/dikhta hai (username/password), CNIC/phone format, duplicate, photo, salary + components.
- Teaching assignments (clash, cover-a-subject), salary components (allowance/deduction).
PHASE 4 · FEE + STUDENTS
- Fee heads, discount types, sibling discount, fine slabs, fee structures (single class aur multi-class/multi-campus).
- Students ka admission: alag alag class/section/campus mein; siblings ek hi guardian ke saath; ek guardian jis ka phone doosre guardian
  se sirf aakhri 6 digits mein same ho (crash nahi hona chahiye); fee mode teeno tarah (structure se, discount ke saath, manual).
  Login passwords wali screen, copy, dobara na dikhe. Student list/profile/ID card/enquiry (enquiry se admission convert).
PHASE 5 · DAILY OPERATIONS (sab module, ek school ka asli mahina)
- Attendance (register, late, absent, lock), leave application.
- Vouchers generate (dobara generate bhi), print/challan, payments (poori, partial, reversal), receipt, fee dashboard/reports.
- Exam: paper timetable, registration, marks entry, grace, result, position, report card, date sheet, publish.
- Timetable: periods, hafta ka grid, clash. (/timetable pehle error deta tha; ab check karo.)
- Transport (vehicle, route, stops, assignment, expense), Inventory (type, item, supplier, purchase, student issue/return).
- Finance (category, receive, make payment, reports) aur check karo fee/purchase/payroll ki entries yahan aati hain.
- Payroll run + salary slip print.
PHASE 6 · REVIEW (owner ki nazar se)
- "School zero se le kar pehle student ki fee wasooli tak" kitne pages/clicks? Kahan atke? Kaunsa step order mein pehle hona chahiye tha par
  app ne nahi bataya? Kaunsi cheez ek hi jagah ho sakti thi? Kaunsa page/field/label confusing ya bekaar laga?
- Sidebar ka har item ab data ke saath ek baar kholo: koi page toota/ajeeb?

AAKHIR MEIN LOG BLOCK ke saath:
DATA CREATED: (campuses, session, classes+sections, subjects, staff + logins/passwords jo app ne diye, students + guardians + logins,
  fee structures, exam, route, inventory — sirf naam aur ek-line)
SETUP ORDER PROBLEMS: (jahan app ne guide nahi kiya)
```
