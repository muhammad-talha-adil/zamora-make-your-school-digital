# 01g · AAKHRI round: Fee structure + Admission (baaki scenarios + naye fixes verify)

Same browser chat mein paste karein (MASTER PROMPT pehle se hona chahiye). Ctrl+Shift+R karo.
LOGIN: pehle se owner@school.com se login hai to logout/login MAT karo; sirf session expire ho to login.

```
FOCUS: sirf Fee Structure + Student Admission. Baaqi module mat kholo. DB reset/delete nahi. "T-4 Class 6 huge amount" delete mat karo.
Har item PASS/FAIL + ek line. Na ho sake to "NOT TESTED + wajah". Naye test data ka naam "T-<number> ...". MASTER PROMPT P1-P10 + SPC.

PART 0 · NAYE FIXES VERIFY
V1 Sibling admission: phone se existing guardian aaye, email auto-bhari, readonly; submit ho jaye (email error nahi).
V2 Urdu naam (student + guardian) save ho; "Ali 123" reject saaf message ke sath.
V3 Decimal amount 1234.567: form 2 decimal par rok de / message "Amount can have at most 2 decimal places." (raw items.0.amount nahi).
V4 Voucher Generate: student ki class/section chuno: structure card dikhe (No Fee Structure Found nahi), "Not in this voucher" note, Monthly total mein optional alag "+ Rs X optional".
V5 Structure create/edit/show: Monthly total optional ke baghair; item row mein "Aug-Jun" range.
V6 Student profile: top Monthly Fee agreed/net dikhe (discount wale student par), structure rate chhota.
V7 Structure delete -> "Make it Inactive" ek click mein (dusra confirm nahi). V8 Payment reversal khali reason: inline "Please write the reason."
V9 T-4 (khali structure) Class 6-A ke admission mein na aaye.
V10 Percentage discount 400 daalo: 100 se zyada rok de / saaf message.
V11 Draft structure list mein "Draft" likha dikhe (Inactive nahi). Draft/Inactive ko activate karo jab overlap ho: message "Another active structure already covers <class>. Make it Inactive first."
V12 Structure title 200 se lamba: limit lage, 170 ke baad counter. Fee item update par toast "Fee item updated".
V13 Structure edit (jis par voucher bane hon): note "N voucher(s) already made... Changes apply to new vouchers only." Edit par Class zaroori, "leave empty for all classes" nahi.
V14 Structure list: Actions column bina horizontal scroll nazar aaye; Campus column nahi.
V15 Delete band structure par: dialog title "Cannot delete", message saaf; active ho to "Make it Inactive" button ek click mein; inactive ho to "already Inactive" wala message.
V16 "Default" ka koi button/badge structures list ya voucher form mein nahi (hata diya gaya).

PART 1 · FEE STRUCTURE baaki (ID | PASS/FAIL | ek line)
S4  All Campuses + kai classes: har campus ke liye alag structure? label saaf?
S9  Draft / Active / Inactive: draft aur inactive admission aur voucher form mein nazar na aayein.
S10 Ek class par do ACTIVE structure banane ki koshish: reject? Admission mein picker kab aata hai?
S11 Active + draft allowed? Draft ko active karte hi overlap reject?
S12 Edit: amount badlo, item hatao, class badlo: purane vouchers par asar + warning?
S13 Khali title, bohot lamba title, same head do baar.
S15 List: ek saath bane records (class x section) kitne rows? padhne mein aasaan? behtar tareeqa batao.

PART 2 · ADMISSION baaki (ID | PASS/FAIL | ek line). Har student: form total = voucher = profile Fee card?
A-3  Johar Town (T-6) aur All-campus class mein student.
A-8a Percentage discount (10%): total, reason. A-8b NAYA Fixed discount: Apply Discount -> type chuno -> "Discount In" = Fixed amount (Rs.) -> 500 -> Monthly Fee tick -> reason likho; preview "Rs 5,000 - Rs 500 = Rs 4,500"? Fee se zyada amount par submit ruke? Admit ke baad edit karo: type/value/reason wapas aaye? Profile Fee card aur naye voucher par original/discount/net sahi? A-8c Sibling discount (do bachay ek guardian). A-8d dobara discount lagana.
F7   Discount wale student ka NAYA voucher: original, discount, net teeno dikhein?
A-9  Fee Heads (manual) mode: 0 amount, head hata do, custom amount typing, reason.
A-10 Structure uthao phir ek amount badlo: "custom" label/reason.
A-13 Mid-year admission (admission date session ke beech): pichle mahine ki fee? Part-year item (Aug-Jun) ka asar.
A-14 Future DOB, double-click submit (do student to nahi bane?), form beech mein refresh, lamba naam.
A-17 Admission ke baad student list mein turant dikhe; profile Fee card numbers voucher se match.

PART 3 · UX: faltu/confusing cheez, clicks (structure banane se pehla voucher tak), NEW IDEAs.

AAKHIR: PART 0/1/2 tables + LOG BLOCK (sirf fee/admission) + DATA CREATED + CONFUSING/REMOVE + NEW IDEAS +
ek line: "Fee structure aur admission SATISFIED / NOT SATISFIED, kyun" aur agar NOT SATISFIED to kaun se 3 item sabse zaroori.
Context khatam hone wala ho to pehle ab tak ke tables do.
```

## Iske baad (hisaab rakha hua)
Agar fee structure + admission SATISFIED aaye to AGLA module: **Student fee payment aur records** (vouchers, payments, receipt, partial/reversal, fee records/reports). Pehle prompt 02 draft dekh kar usi par.
