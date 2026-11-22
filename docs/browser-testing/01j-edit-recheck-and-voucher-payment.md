# 01j · Edit page re-check + AGLA MODULE: Voucher generation + Fee payment + Records

Same browser chat mein paste karein. Ctrl+Shift+R (naya build aaya hai). Login pehle se hai to logout/login MAT karo.

```
LOGIN: pehle check karo owner login hai ya nahi. Na ho ya session expire ho to hi login karo: Email owner@school.com / Password 123456. Warna logout/login mat karo.
FOCUS: Part A (edit re-check, chhota) phir Part B (voucher + payment + records). Public website/baaqi module mat kholo. DB reset/delete nahi. Naya data "T-<number> ...". "T-4 Class 6 huge amount" delete mat karo. MASTER PROMPT P1-P10.
Har item PASS/FAIL + ek line + click count. Na ho sake to "NOT TESTED + wajah".

PART A · EDIT PAGE RE-CHECK
E1 Student 16 (T Fifteen) edit kholo: Section bhara hua? Fee panel structure dikhaye (No fee structure NAHI)?
E2 Student 17 edit: guardian phone hyphen ke sath (0311-1110066)? Bina kuch badle Update ho jaye?
E3 Student 16 edit: purana discount wapas aaye: type, Percent/Fixed, value, reason, ticked heads. Bina badle save karo: discount wahi rahe?
E4 Naam 100 se lamba type karo: input rok de ya counter dikhe; submit par saaf message.
E5 Admission se pehle ke mahine ka voucher (Nursery-A June): page par saaf message aaye ("admitted in ... not allowed")?

PART B · VOUCHER GENERATION
V1 Single student voucher: Generate form mein student/class/section chuno, mahina: structure card, total = profile Fee card? Kitne clicks?
V2 Bulk (poori class/section ek mahine): kitne bane, kitne skip (admitted later / pehle se bana hua)? Result message saaf?
V3 Dobara same mahina generate: duplicate nahi banna chahiye, message saaf.
V4 Voucher detail: original, discount, net, due date, items (monthly + annual/once agar mahina ho). Discount wale student par teeno dikhein.
V5 Optional item / transport item: sirf agreed ho to voucher mein aaye.
V6 Previous dues: pichla unpaid voucher naye voucher mein "arrears/previous dues" ke taur par aaye ya alag? Double count to nahi?
V7 Voucher list: filter (class, section, month, status), search student, columns padhne mein aasaan? Voucher print/PDF.

PART C · FEE PAYMENT (har payment ke baad voucher status + balance + receipt check)
P1 Poori payment: status Paid, balance 0, receipt number, receipt print/PDF.
P2 Partial payment (e.g. 40%): status Partial, balance sahi; baaqi payment baad mein: Paid.
P3 Overpayment / zero / negative / decimal amount: reject ya saaf message?
P4 Payment method (cash/bank/online): reference number zaroori kab? Date future mein?
P5 Payment form: Received Amount pehle se bhara, fewest clicks? Student se seedha payment (profile se) ka raasta?
P6 Payment reversal: reason zaroori, voucher wapas Unpaid/Partial, receipt ka kya hota hai, dobara reverse na ho.
P7 Double-click payment submit: ek hi payment? Do tabs mein same voucher par payment: dusra reject?
P8 Ek guardian ke do bachay: dono ka alag payment; (agar family payment ka option ho to dekho).
P9 Late fee / fine / concession agar hai to kaise kaam karta hai; nahi hai to likho "nahi hai".
P10 Payment ke baad student profile Fee card aur dues turant update?

PART D · RECORDS / REPORTS
R1 Payments list: filter (date, class, method, student), total sahi? Receipt dobara print.
R2 Defaulters / unpaid dues list: class wise, amount sahi? Export (CSV/PDF) agar hai.
R3 Daily collection / summary: aaj ki payments ka total = payments list ka total?
R4 Student fee ledger/history: voucher + payment + reversal sab dikhein, balance sahi?
R5 Numbers match: ek student ka profile Fee card = voucher = payments = ledger = dues list?

PART E · UX: faltu/confusing cheez, clicks (voucher se receipt tak), NEW IDEAs.

AAKHIR: tables (A, B, C, D) + LOG BLOCK + DATA CREATED + CONFUSING/REMOVE + NEW IDEAS + do verdict lines:
1) "Fee structure aur admission SATISFIED / NOT SATISFIED, kyun" (NOT ho to top 3).
2) "Voucher aur fee payment SATISFIED / NOT SATISFIED, kyun" (NOT ho to top 3).
Context khatam hone wala ho to pehle ab tak ke tables do.
```
