# 01p · Fee LAST round (01o fixes + jo ab tak test nahi hua)

Same browser chat mein paste karein. Ctrl+Shift+R. Login pehle se hai to logout/login MAT karo.

```
LOGIN: pehle check karo owner login hai ya nahi. Na ho to hi login karo: owner@school.com / 123456. Campus Gulberg. Logout/login baar baar mat karo.
FOCUS: sirf Fee. DB reset/delete nahi. Naya data "T-<number> ...". MASTER PROMPT P1-P10.
Har item PASS/FAIL + ek line. Na ho sake to "NOT TESTED + wajah". FAIL par exact message/number. Window resize ke liye browser tool ka resize use karo (phone = 400px), har check ke baad size dobara confirm karo.

PART 1 - 01o fixes
P1 Collection report Export CSV: file poori (sab rows, Method column bhara). Outstanding + Defaulters CSV bhi. Defaulters ka Export CSV button kata nahi? Class dropdown mein duplicate nahi?
P2 Ledger (T Eight, T-n Final): running balance kisi line par negative nahi? Reversal payment ke bilkul neeche? Aakhri balance = Outstanding?
P3 Voucher FV-2026-000017 (detail + print): late fee sirf Fine line mein, items list mein nahi; Gross 5,000, Net 5,200?
P4 Receipt (payments list se kisi payment ka View aur Print dono): school ka naam, items, paid, concession + reason, late fee, Remaining Balance dikhte hain? Month sahi?
P5 Record Payment fresh form: koi laal message nahi jab tak kuch type/submit na ho. Student chuno, amount 0 karo: field ke neeche saaf message, Record disabled? Negative type hi nahi hota? 1 crore se bada: message?
P6 Bada amount (balance se zyada, limit ke andar): amber "wallet credit" message + confirm tick; tick ke bina Record disabled; tick par ho jata hai? (naya payment mat banao agar zaroori na ho; sirf form tak dekho)
P7 Family page: Pay button tab tak disabled jab tak voucher tick + valid amount na ho; messages saaf?
P8 Phone width 400px: Record Payment (dues cards, Pay input poori width), Family, Vouchers list, Payments list, Receipt, 3 reports: kuch kata nahi?

PART 2 - jo ab tak test nahi hua
P9 Receipt Print/PDF preview: school ka naam, items, balance print mein bhi?
P10 Payment Method report (agar hai): Cash/Bank etc. ke totals Collection report se match?
P11 January Annual fee: naya student/ya structure mein Annual fee head, Jan voucher generate: Annual fee sirf Jan mein aati hai, baaqi mahinon mein nahi?
P12 Transport / Inventory dues (agar module data hai): voucher mein alag line, payment mein "Inventory" tag, total sahi? Na ho to NOT TESTED.
P13 Johar Town campus: campus badlo, Fee pages (vouchers, payments, reports) khulte hain, sirf us campus ka data? Gulberg ka data leak nahi? Wapas Gulberg.
P14 Bohot saari vouchers par load: Vouchers list (50+ rows) pagination, search, filters tez chalte hain? Koi timeout/500 nahi?
P15 Fee Heads / Structures / Discount types / Fine Rules forms: khali submit par message saaf (field ke neeche)? Negative number type nahi hota?
P16 Poora final flow ek aakhri baar naya student "T-p Final": admit -> voucher -> concession+late fee ke saath partial -> baaqi -> receipt -> reverse -> ledger/Outstanding/Defaulters/Profile sab match.

AAKHIR: table (P1-P16) + LOG BLOCK + DATA CREATED + CONFUSING + ek line "Fee module FINAL SATISFIED / NOT SATISFIED, kyun" (NOT ho to top 3).
Context khatam hone wala ho to pehle ab tak ke tables do.
```
