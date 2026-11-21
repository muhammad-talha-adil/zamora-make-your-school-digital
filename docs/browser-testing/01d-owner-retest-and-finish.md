# 01d · Owner: sab fixes dobara verify + baaki phases (DB reset nahi, purana data maujood)

Same browser chat mein paste karein (MASTER PROMPT pehle se hona chahiye). Pehle logout/login + Ctrl+Shift+R.

```
Naya round, sirf owner@school.com / 123456. DB mein pichla data maujood hai (2 campuses, session, classes, staff, 2 students, fee structures).
Kuch reset/delete mat karna. MASTER PROMPT P1-P10 har page par lagao. HAR item ka PASS/FAIL + ek line zaroor likho (table mein), koi item skip na ho.

PART A · FIX VERIFY (table: ID | PASS/FAIL | ek line)
A1 Dashboard hero text safed; sab blue buttons (Add Session, Add Campus...) par text safed, light + dark; koi jhoota trend badge nahi; Rs.
A2 Settings tables: classes/sections/subjects mein Code/description nahi; classes table mein Campus; natural order; exam type hint.
A3 Naya session form: April-March dates khud bhari; 13 mahine se lamba reject; shift timing dates bhari.
A4 Bulk classes/sections/subjects: saare campuses selected; bulk sections mein capacity nahi.
A5 Class-subject mapping: campus picker nahi, class chunte hi subjects load, Section khali = sab sections.
A6 Banner "Create Campus" -> modal (banner na ho to skip likho).
A7 Toasts/errors (save, duplicate, validation, delete, edit): aam zaban, chhoti line.
A8 Staff > Add: CNIC/phone/photo/campus; save ke baad login card (rows: Username, Email, Password + Copy) ek baar; reload par password nahi; "Make new password" kaam kare.
A9 Staff permissions: aam labels; designation quick-add.
A10 Staff form: sirf "Position" field; Role picker tab hi dikhe jab position ka role link na ho ("Access level"); Head Teacher role list mein hai.
A11 /timetable error? /portal 404? campus switcher mein dono campus.
B1 Student create: dono guardians ka CNIC khali chhodo -> save ho jaye (SQL error nahi). Duplicate CNIC -> saaf message.
B2 /students bina search ke saare students dikhein (nayi admission ke turant baad bhi).
B3 /students?campus_id=2 filter kaam kare.
B4 Fee structure: same class/section par doosra active structure banao -> reject + saaf message. (DUP wala purana structure delete kar do, ye allowed hai.)
B5 Admission form: ek se zyada structure match hon to picker; koi na ho to "Add fee structure" button.
B6 Fee Heads tab custom amount keyboard se type ho (1500 type karo).
B7 Submit disabled ho to "Still needed:" hint; galat submit par pehle error tak scroll.
B8 Fee structure create, All Campuses: classes ke saath campus label; koi faltu "No results found" box?
B9 Fee structures list: class bold + section chip; list ab bhi lambi/confusing? behtar tareeqa batao.
B10 Do guardian jinke phone ke aakhri 6 digit same hon: "Use the same guardian?" warning, "Use this guardian" / "No, different" buttons.

PART B · BAAKI KAAM (maujooda data par)
Phase 3: teaching assignments (clash + cover), salary components (allowance/deduction), baaqi staff (head teacher, clerk, driver).
Phase 4: Apply Discount mode, Johar Town aur All-campuses fee structure, ID card, enquiry->admission.
Phase 5: attendance (+late/lock/leave), vouchers (dobara generate bhi), payments (poori/partial/reversal), receipt, exam+result,
         timetable, transport, inventory, finance entries, payroll run + salary slip.
Phase 6: owner review: zero se pehli fee wasooli tak kitne pages/clicks, kahan atke.
Har phase ke baad "PHASE n DONE: pages N, bugs N". Atko to workaround se aage badho. Agar context khatam hone wala ho to pehle LOG BLOCK do, phir batao kya baaki hai.

AAKHIR MEIN: FIX VERIFY TABLE (A1-A11, B1-B10) + LOG BLOCK + DATA CREATED + SETUP ORDER PROBLEMS + NEW IDEAS.
```
