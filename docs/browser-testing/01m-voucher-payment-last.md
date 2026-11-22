# 01m · Voucher + Payment: 01l fixes re-check (chhota round)

Same browser chat mein paste karein. Ctrl+Shift+R. Login pehle se hai to logout/login MAT karo.

```
LOGIN: pehle check karo owner login hai ya nahi. Na ho ya session expire ho to hi login karo: Email owner@school.com / Password 123456. Warna logout/login mat karo.
FOCUS: sirf Voucher + Fee Payment + reports. Baaqi kuch nahi. DB reset/delete nahi. Naya data "T-<number> ...". MASTER PROMPT P1-P10.
Har item PASS/FAIL + ek line + click count. Na ho sake to "NOT TESTED + wajah".

L1 Auto late fee: May 2027 Nursery-A purane vouchers (FV-2027-000018..22) par ab Late Fee Rs 6,350 nahi? Total = items + arrears sirf? Naya mahina (Jun 2027) "Include previous unpaid balance" se generate: koi khud-ba-khud late fee nahi?
L2 Gross: FV-2026-000017: Gross = sirf items (5,000), late fee alag line, Net sahi?
L3 Generate message: Nursery Nov+Dec dobara generate: popup "Created N. X skipped: already made..." aaye? Naya mahina generate: "Created N" popup aaye? Mixed run bhi.
L4 Concession reason: naya payment (T Nine, concession 100 + reason "T-test"): voucher detail + receipt par reason + late fee/concession lines dikhein?
L5 Typed future date (keyboard 30 Dec 2026): ab date ke neeche saaf message aaya? (single payment + family page dono)
L6 Family page: amount balance se zyada likho: button disable + row ke neeche "More than the balance"? 
L7 Carry-forward purane voucher (e.g. voucher 20) par payment: saaf message "carried forward... pay that one"?
L8 Defaulters: Class column hai? Days overdue sahi (Jul 10 se aaj = 135)?
L9 Do tabs: ek tab mein voucher ka payment karo, dusre tab (purani page) mein wahi: "already paid" message ke saath reject?
L10 Payment form (Record Fee Payment) badi screen par poori width, fields seedhe?
L11 Payments list / Collection report / Outstanding report load (koi 500 nahi), numbers: T Eight profile Fee card = ledger = Outstanding = Defaulters.

AAKHIR: table (L1-L11) + LOG BLOCK + DATA CREATED + CONFUSING + ek line "Voucher aur fee payment SATISFIED / NOT SATISFIED, kyun" (NOT ho to top 3).
Context khatam hone wala ho to pehle ab tak ke tables do.
```
