# 01k · Voucher + Payment + Reports: 01j fixes re-check + naye features

Same browser chat mein paste karein. Ctrl+Shift+R (naya build). Login pehle se hai to logout/login MAT karo.

```
LOGIN: pehle check karo owner login hai ya nahi. Na ho ya session expire ho to hi login karo: Email owner@school.com / Password 123456. Warna logout/login mat karo.
FOCUS: sirf Voucher + Fee Payment + Fee reports. Public website/baaqi module mat kholo. DB reset/delete nahi. Naya data "T-<number> ...". "T-4 Class 6 huge amount" delete mat karo. MASTER PROMPT P1-P10.
Har item PASS/FAIL + ek line + click count. Na ho sake to "NOT TESTED + wajah".

RE-CHECK (01j ke fixes)
F1 Double-click Record Payment: sirf 1 payment? Do tabs mein same voucher par overpay: dusra reject?
F2 Payment date future (30 Dec): reject + saaf message? Date picker mein aaj ke baad ki date na mile?
F3 Outstanding Report khule (500 nahi), numbers sahi?
F4 Collection Report: reversed payments total mein nahi? Reversed/late fee/concession alag line? Payment Method report bhi?
F5 Payments list: Student naam + REG dikhe (N/A nahi), newest upar, search kaam kare?
F6 "Include previous unpaid balance" (Nursery-A, naya mahina): total = items + previous balance (arrears line alag), net/balance Rs 0 nahi, double count nahi? (Purane Feb 2027 vouchers dekho, naya bhi banao.)
F7 Due date: July/August jaise purane mahine ka voucher: due = us mahine ki 10 tareekh (guzri hui)? Defaulters list mein aaye?
F8 Voucher list Search: voucher no, REG, student naam teeno se mile?
F9 Pre-admission mahina (Nursery-A June): popup/message dikhe "admitted in ... not allowed"? Mixed run: "N skipped: admitted later"?

NAYE FEATURES
N1 Late fee: payment form mein Late fee (e.g. 200) daalo: voucher fine/net badhe, balance sahi, Paid tab jab poora settle. Negative/decimal(3 digit) reject?
N2 Concession: amount (e.g. 300) + reason zaroori (khali reason reject). Balance kam, items oldest first adjust. Concession > balance reject.
N3 Late fee + concession dono ek payment mein: voucher detail, receipt, ledger mein sahi?
N4 Payment reverse (late fee/concession wali): dono wapas? voucher status/balance purana?
N5 Fee ledger: student profile se "Fee ledger" link. Voucher + payment + reversal + carry-forward, running balance sahi? Profile Fee card = ledger balance?
N6 Family payment: ek guardian ke 2 bachay (T Eight..Seventeen mein sibling dhoondho ya naya banao). Profile se "Pay family fees": sab unpaid vouchers checkbox se, amount prefilled, oldest first, overpay reject, double-click safe. Har bachay ka alag receipt? Receipt/ledger mein sahi?
N7 Defaulters list: overdue vouchers aayein, class-wise, amount sahi? Export agar ho.

NUMBERS MATCH
M1 Ek student: profile Fee card = voucher = payments list = ledger = Defaulters/Outstanding report?
M2 Aaj ki Collection report total = payments list (reversed nikal ke)?

UX: faltu/confusing cheez, clicks (voucher se receipt tak), NEW IDEAs.

AAKHIR: tables (F, N, M) + LOG BLOCK + DATA CREATED + CONFUSING/REMOVE + NEW IDEAS + ek line "Voucher aur fee payment SATISFIED / NOT SATISFIED, kyun" (NOT ho to top 3).
Context khatam hone wala ho to pehle ab tak ke tables do.
```

## Iske baad
SATISFIED aaye to agla module (user se pooch kar): Attendance / Exams / Staff payroll.
