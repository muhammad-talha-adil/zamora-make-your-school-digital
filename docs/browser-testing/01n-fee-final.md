# 01n · Fee FINAL round (01m fixes + end-to-end check)

Same browser chat mein paste karein. Ctrl+Shift+R. Login pehle se hai to logout/login MAT karo.

```
LOGIN: pehle check karo owner login hai ya nahi. Na ho to hi login karo: owner@school.com / 123456. Login ke baad header campus "Main Campus Gulberg" select karo (Johar Town default aa sakta hai). Logout/login baar baar mat karo.
FOCUS: sirf Fee structure + Voucher + Fee Payment + reports + student profile fee. DB reset/delete nahi. Naya data "T-<number> ...". MASTER PROMPT P1-P10.
Har item PASS/FAIL + ek line + click count. Na ho sake to "NOT TESTED + wajah". FAIL par exact message/number likho.

PART 1 - 01m fixes
M1 Purane May 2027 vouchers (FV-2027-000019, 20): Late Fee 6,350 ab nahi? Total = items + arrears?
M2 FV-2026-000017: Gross = 5,000, late fee 200 alag line, Net 5,200?
M3 Payment detail page + Receipt: concession amount + reason + late fee lines dikhein? (naya payment, concession 50, reason "T-n1")
M4 Typed future date (keyboard 30 Dec 2026): date ke neeche "Payment date cannot be in the future." (single payment + family page)?
M5 Carry-forward voucher (adjusted, e.g. voucher 20) par pay: banner "carried forward... pay that one" + link?
M6 Do tabs: tab A mein voucher pay, tab B (purani page) se wahi: "already paid" message dikha?
M7 Defaulters: T Eight (Nursery) ab list mein? Class column, days overdue sahi?
M8 T Eight: profile Fee card outstanding = ledger = Outstanding report = Defaulters (Rs 62,750 ya jo ho, sab ek hi number)?

PART 2 - student fee end-to-end (naye student T-n Final)
M9 Naya student admit karo (Nursery, fee structure ke saath, 1 discount/concession). Voucher generate: items, discount, due date 10th sahi?
M10 Partial payment (late fee ke saath), phir baaqi payment: voucher status unpaid -> partial -> paid, balance sahi har step?
M11 Payment reverse karo (reason ke saath): voucher balance wapas, late fee/concession wapas, ledger mein reversal line?
M12 Receipt print: school name, student, voucher no, month, items, paid, balance, date sab sahi? Duplicate receipt no nahi?
M13 Sibling/family: 2 bachon ka ek payment: har bache ki alag receipt, amounts sahi, overpay block?
M14 Student ledger: running balance har line ke baad sahi, serial "#" column, last balance = Outstanding report?
M15 Next month generate (previous unpaid balance ke saath): arrears sahi aaye, purana voucher "adjusted", double count nahi?
M16 Reports: Payments list = Collection report = Outstanding = Defaulters; filters (class/month) kaam karein; Excel/print export (agar hai) khule?
M17 Chhote screen (phone width ~400px) par voucher list / payment form / receipt: kuch kata ya bahut khali nahi?
M18 Galat input: negative amount, 0 amount, bahut bada amount, khali method, concession reason ke bina concession: har ek par saaf message?

AAKHIR: table (M1-M18) + LOG BLOCK + DATA CREATED + CONFUSING + ek line "Student fee (structure, voucher, payment) SATISFIED / NOT SATISFIED, kyun" (NOT ho to top 3).
Context khatam hone wala ho to pehle ab tak ke tables do.
```
