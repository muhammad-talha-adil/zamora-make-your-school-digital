# 01f · Fee structure + Student admission: fixes verify + baaki scenarios (sirf yehi 2 cheezein)

Same browser chat mein paste karein (MASTER PROMPT pehle se hona chahiye). Ctrl+Shift+R karo.
LOGIN: agar pehle se owner@school.com se login hai to logout/login MAT karo. Sirf tab login karo jab login na ho ya session expire ho jaye.

```
FOCUS: sirf (1) Fee Structure creation (2) Student admission fee scenarios. Baaqi koi module mat kholo. DB reset/delete nahi (purana data rakho).
Pehle check karo owner@school.com se login hai? Haan to wahi chalao, bar bar logout/login nahi. MASTER PROMPT P1-P10 + SPC.
Har item ka PASS/FAIL + ek line + click count. Koi item skip nahi; na ho sake to "NOT TESTED + wajah". Atko to workaround se aage badho.
Naye test structures ka naam "T-<number> ..." rakho. Purana "T-4 Class 6 huge amount" structure delete mat karo.

PART 0 · PICHLE FIXES VERIFY (ID | PASS/FAIL | ek line)
F1  Item amount 99999999999 -> saaf message (500 nahi), koi adha structure list mein nahi bana; khali items -> "Add at least one fee head".
F2  Student profile par Monthly/Annual Fee asli (Rs 0 nahi) + Fee card (structure, totals, discount, aakhri vouchers).
F3  Voucher generate form aur admission panel mein "Not in this voucher: Annual fee is billed in <month>" / one-time note nazar aata hai?
F4  "DUP Class 1 Overlap Test" delete try karo: "Used by N vouchers. You can make it Inactive instead." + "Make it Inactive" button. Inactive karke dekho.
F5  Create Structure disabled ho to "Still needed: ..." hint.
F6  Student name mein ajeeb character: saaf message.
F7  Agreed amount structure se kam: voucher par original, discount, net teeno.
F8  Admission panel header: Monthly | Annual | One-time total sahi?
F9  Structure create/edit item row: "How often" + "More options" (Optional, Charged at admission, Transport fee, start/end month, billing month/year). Edit mein Effective From/To nahi.
F10 Voucher se Payment page kholo: Received Amount pehle se bhara.
F11 Structure form mein jo fee head add ho chuki wo dobara list mein nahi.
X1  "Copy from existing structure" kaam kare (items + flags copy). X2 All Campuses default mein tick nahi.

PART 1 · FEE STRUCTURE baaki scenarios (table: ID | PASS/FAIL | ek line)
S3  1 campus + Nursery, Prep, Class 1 ek sath same fees: kitne records bane, list kaisi, clicks?
S4  All Campuses + kai classes: har campus ke liye alag structure? label saaf?
S6  Item flags: Lab fee optional, Admission "charged at admission", Transport "transport fee" — admission form mein asar.
S7  Frequency mix: monthly + yearly (billing month+year) + once.
S8  Part-year: Tuition August se June (start/end month); Annual sirf ek mahine.
S9  Draft vs Active vs Inactive: draft/inactive admission mein nazar na aaye.
S10 Default structure (is_default): matlab saaf? doosre class par kya hota hai?
S11 Active+draft allowed? Draft ko active karte hi overlap reject?
S12 Edit: amount/item badlo, item hatao, class badlo: purane vouchers par asar + warning?
S13 Zero, negative, decimal, 9999999.99, same head do baar, khali/lamba title.

PART 2 · STUDENT ADMISSION baaki scenarios (table: ID | PASS/FAIL | ek line; har baar form total = voucher = profile Fee card?)
A-3  Multi-class / all-campus structure wali class: dono campus mein ek ek student.
A-5  Ek se zyada structure match (active+draft): picker? kaun sa chuna?
A-6  Optional item tick/untick: total badalta hai? voucher mein sirf ticked?
A-7  Transport-related item (route nahi): unticked default? total mein nahi?
A-8  Discount: percentage, fixed, sibling; reason; dobara discount.
A-9  Fee Heads (manual) mode: custom amount typing, 0 amount, head hata do.
A-10 Structure + ek amount manual badlo: label/reason.
A-11 Sibling (ek guardian, 2 bachay) + same-last-6-digits guardian warning.
A-13 Mid-year admission: pichle mahine ki fee? part-year item ka asar.
A-14 Galat data: khali guardian CNIC, duplicate CNIC, lamba/Urdu naam, future DOB, double-click submit, refresh beech mein.
A-15 Admission ke baad "Generate first voucher": admission fee + pehle mahine ki tuition sahi, ek click?
A-16 Voucher payment: poori + partial + reversal (sirf in students par).

PART 3 · UX: faltu/confusing field, defaults, structure se admission tak clicks, NEW IDEAs.

AAKHIR: PART 0 table + S table + A table + LOG BLOCK (sirf fee/admission) + DATA CREATED + CONFUSING/REMOVE + NEW IDEAS + ek line "Fee structure aur admission mere hisaab se SATISFIED / NOT SATISFIED kyun".
Context khatam hone wala ho to pehle ab tak ke tables do aur batao kya baaki hai.
```
