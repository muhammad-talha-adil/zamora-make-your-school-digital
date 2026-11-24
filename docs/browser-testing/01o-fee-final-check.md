# 01o · Fee FINAL check (01n fixes + numbers match)

Same browser chat mein paste karein. Ctrl+Shift+R. Login pehle se hai to logout/login MAT karo.

```
LOGIN: pehle check karo owner login hai ya nahi. Na ho to hi login karo: owner@school.com / 123456. Campus ab yaad rehna chahiye; header "Main Campus Gulberg" ho. Logout/login baar baar mat karo.
FOCUS: sirf Fee (voucher, payment, reports, profile fee card). DB reset/delete nahi. Naya data "T-<number> ...". MASTER PROMPT P1-P10.
Har item PASS/FAIL + ek line + click count. Na ho sake to "NOT TESTED + wajah". FAIL par exact message/number likho.

N1 Numbers match: T Eight aur T-n Final ke liye 4 jagah ka baqaya likho: Profile Fee card, Ledger summary, Outstanding report, Defaulters "Total Outstanding". Sab barabar?
N2 Defaulters: ek student = ek row; "Overdue Amount" aur "Total Outstanding" columns; jin vouchers ki due date abhi aayi nahi woh list mein nahi; "0 days" wali row nahi; Class column hai; days sahi?
N3 Defaulters / Outstanding: Class aur Month filter kaam karte hain? Export CSV teeno reports (Payments/Collection, Outstanding, Defaulters) par file download hoti hai?
N4 Profile Fee card: outstanding + overdue dikhte hain?
N5 Ledger: "Carried forward" line payments ke baad, running balance kabhi negative nahi, "#" column, aakhri balance = Outstanding?
N6 Voucher detail: late fee sirf ek jagah (Fine line), items list mein dobara nahi? Gross = sirf items?
N7 Carry-forward voucher (adjusted) par pay: banner + link seedha aakhri OPEN voucher par jata hai?
N8 Receipt: school ka naam, items list, paid, Remaining Balance, concession + reason, late fee sab dikhte hain? (print preview bhi)
N9 Payment form placeholders: Student search, Late fee, Concession, Concession reason, Received Amount sab mein placeholder text hai?
N10 Galat input (Record Payment): amount 0, negative, khali method, concession bina reason, future date, 1 crore se bada amount: har ek par field ke neeche apna laal message (browser bubble nahi)? Button chupke disable to nahi?
N11 Bada amount (voucher balance se zyada, limit ke andar): "This will add Rs X to wallet credit" message + confirm tick maangta hai?
N12 Family page: placeholders, overpay "More than the balance", future date message, galat input messages?
N13 Reverse payment: reason khali par inline message; reverse ke baad voucher balance, late fee, concession wapas; ledger mein Reversal line?
N14 Generate voucher: "Created N / skipped X" popup; naya mahina previous balance ke saath: auto late fee nahi, arrears sahi, purana voucher adjusted?
N15 Phone width (~400px): Voucher list, Record Payment (dues table scroll, Pay Amount dikhe), Family, Receipt, reports: kuch kata nahi, bahut khali jagah nahi? Badi screen par Vouchers/Payments card list mein khali jagah nahi?
N16 Do tabs: ek tab mein pay, purane tab se wahi: "This voucher is already paid." message?
N17 Naye student ka poora flow ek baar: admit -> first voucher -> partial pay -> baaqi pay -> status paid -> receipt. Sab sahi?
N18 Koi 500 / console error kisi Fee page par?

AAKHIR: table (N1-N18) + LOG BLOCK + DATA CREATED + CONFUSING + ek line "Fee module FINAL SATISFIED / NOT SATISFIED, kyun" (NOT ho to top 3).
Context khatam hone wala ho to pehle ab tak ke tables do.
```
