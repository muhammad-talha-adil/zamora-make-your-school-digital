# 01l · Voucher + Payment: 01k fixes re-check (AAKHRI round)

Same browser chat mein paste karein. Ctrl+Shift+R. Login pehle se hai to logout/login MAT karo.

```
LOGIN: pehle check karo owner login hai ya nahi. Na ho ya session expire ho to hi login karo: Email owner@school.com / Password 123456. Warna logout/login mat karo.
FOCUS: sirf Voucher + Fee Payment + reports. Baaqi kuch nahi. DB reset/delete nahi. Naya data "T-<number> ...". "T-4 Class 6 huge amount" delete mat karo. MASTER PROMPT P1-P10.
Har item PASS/FAIL + ek line + click count. Na ho sake to "NOT TESTED + wajah".

K1 Purane Rs 0 vouchers (Feb 2027 FV-2027-000005..8, April 2027 FV-2027-000013..17): ab list/detail/ledger/Outstanding/family page sab mein same amount?
K2 "Include previous unpaid balance" (Nursery-A, naya mahina jo pehle nahi bana, e.g. May 2027): vouchers bane? total = items + previous balance, arrears line alag, balance Rs 0 nahi, double count nahi? Popup message saaf?
K3 Due date: T Nine ka August 2026 voucher due 10 Aug 2026? Baaqi purane vouchers bhi mahine ki 10? Defaulters list ab overdue dikhaye (class-wise, amount sahi)?
K4 Voucher Gross sirf items; late fee alag (late fee wale voucher par check).
K5 Concession balance se zyada (e.g. 9999): inline "Concession cannot be more than the balance" aaye, submit ruke.
K6 Concession wale voucher/receipt par reason + late fee/concession lines dikhein?
K7 Ledger (T Eight): rows mahine ki tarteeb mein, Feb row ka Charged bhara, running balance = Outstanding report?
K8 Family payment (T Eight family): shuru mein koi voucher tick nahi, total Rs 0, button "Pay selected (Rs 0)" disabled. Sirf 2 chhote vouchers tick karo: amount fill, total sahi, submit: har bachay ke vouchers Paid, receipt(s), balance sahi? Overpay (amount > balance) reject? Double-click safe? UI columns seedhi?
K9 Outstanding Report: "Class 1 - A" jaise alag labels, duplicate "Class 1" nahi.
K10 Voucher list Search: voucher no, REG, student naam teeno se mile (F8)?
K11 Do tabs: same voucher ka payment ek tab mein karo, dusre tab mein (purani page) wahi payment: reject?
K12 Typed future payment date (keyboard se 30 Dec 2026): reject + message?
K13 Mixed generate run (kuch students pehle se bane, kuch admitted later, kuch naye): popup "Created N. X skipped: already made. Y skipped: admitted later." saaf?

NUMBERS: ek student (T Eight): profile Fee card = voucher list = ledger = Outstanding = Defaulters. Aaj ki Collection report = payments list (reversed alag).

AAKHIR: table (K1-K13) + LOG BLOCK + DATA CREATED + CONFUSING + ek line "Voucher aur fee payment SATISFIED / NOT SATISFIED, kyun" (NOT ho to top 3).
Context khatam hone wala ho to pehle ab tak ke tables do.
```
