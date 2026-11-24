# 01q · Fee CLOSING round (01p fixes + campus/phone auto + jo reh gaya)

Same browser chat mein paste karein. Ctrl+Shift+R. Login pehle se hai to logout/login MAT karo.

```
LOGIN: pehle check karo owner login hai ya nahi. Na ho to hi login karo: owner@school.com / 123456. Campus Gulberg. Logout/login baar baar mat karo.
FOCUS: Fee + jahan campus/phone fields hain (sirf dekhna, naya data "T-<number> ..."). DB reset/delete nahi. MASTER PROMPT P1-P10.
Har item PASS/FAIL + ek line. Na ho sake to "NOT TESTED + wajah". FAIL par exact message/number. Phone check = 400px, resize ke baad width confirm karo.

PART 1 - 01p fixes
Q1 Campus scoping: header campus Johar Town karo: Vouchers list, Payments list, Ledger, Collection/Outstanding/Defaulters/Method reports, Fee dashboard sab sirf Johar ka data (ya khali) dikhayein, Gulberg ka nahi? Wapas Gulberg: sab wapas? Totals header campus ke hisaab se?
Q2 Setup forms (Fee Heads, Fee Structures, Discount Types, Fine Rules): khali submit par field ke neeche saaf message, button chupke disable nahi? Fresh form par laal message nahi? Discount percentage 150 -> message; fixed amount 5 crore -> message?
Q3 Reversed receipt (T-p Final ki reversed payment): "REVERSED" badge, Remaining Balance asli (9,000 ya jo ho), Paid sahi?
Q4 Receipt print: Admission No = ADM-xxxxx (REG alag), late fee alag line (voucher row nahi)?
Q5 Voucher list: koi row pehle se tick nahi? "Print Selected" 0? Select all alag se kaam kare?
Q6 Ledger (T Eight, T-n Final, T-p Final): running balance beech mein bhi phoolta/negative nahi? Aakhri = Outstanding?
Q7 Receipt page UI: phone (400px) aur badi screen: card theek, text kata nahi, buttons wrap, table scroll/stack?

PART 2 - auto-fill rule (user ko type nahi karna chahiye)
Q8 Campus auto: in forms mein campus pehle se Gulberg (header campus) ya hidden/read-only? Dropdown sirf jahan zaroori: Student Edit, Staff Create, Transport (routes/vehicles/etc.), Fine Rules, Structure Edit, Voucher Generate, Finance Receive Payment, Inventory forms. Har form ka naam + result likho. Header campus Johar karke 2 forms dobara: Johar default aaya?
Q9 Phone auto: Student admission (student + guardian phone), Staff Create, Enquiry, Supplier, School settings: "03331112233" type karo -> khud "0333-1112233" ban jaye? "+92 333 1112233" paste -> theek? Save ke baad bina error? Placeholder format dikhta hai? Search boxes mein phone number type karo: format nahi badalta?
Q10 Naye student admission mein phone alag alag format se (dash wala/bina dash) dono save hote hain? Guardian duplicate check phir bhi sahi?
Q11 Dusre auto defaults: Payment date aaj ki, voucher generate month/year current, class chunne par section auto/pehla? Jo aur user ko baar baar type karna parta ho wo likho (CONFUSING mein).

PART 3 - jo reh gaya tha (data khud banao, naya "T-q ...")
Q12 Payment methods: ek chhoti payment Bank/Cheque/Online se karo (T-q student): Method report mein har method ka total alag, Collection report ke barabar?
Q13 Transport fee: agar Transport module mein route/fee set ho sake: T-q student ko transport lagao, voucher mein Transport line alag aaye, payment mein sahi. Na ho sake to NOT TESTED + kya missing.
Q14 Inventory due: agar Inventory se student ko item issue/charge ho sake: voucher mein inventory line, payment form mein "Inventory" tag, total sahi. Na ho to NOT TESTED + wajah.
Q15 Real print: Receipt "Print Receipt" dabao: print preview mein school naam, items, balance, concession, Remaining Balance? (agar dialog na khule to NOT TESTED)
Q16 Poora aakhri flow naya student "T-q Final": admit (phone dash ke bina) -> voucher -> late fee+concession ke saath partial -> baaqi -> receipt -> reverse -> ledger/Outstanding/Defaulters/Profile match.
Q17 Console/500: sab Fee pages + Q8/Q9 wali pages par koi 500/console error?

AAKHIR: table (Q1-Q17) + LOG BLOCK + DATA CREATED + CONFUSING + ek line "Fee module FINAL SATISFIED / NOT SATISFIED, kyun" (NOT ho to top 3).
Context khatam hone wala ho to pehle ab tak ke tables do.
```
