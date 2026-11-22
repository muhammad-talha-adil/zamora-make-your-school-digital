# 01h · Admission: sirf jo 01g mein reh gaya (naya student bana kar)

Same browser chat mein paste karein. Ctrl+Shift+R. Login pehle se hai to logout/login MAT karo.

```
FOCUS: sirf Student Admission + voucher. Baaqi kuch nahi. DB reset/delete nahi. Naye students ka naam "T-<number> ...". MASTER PROMPT P1-P10.
Har item PASS/FAIL + ek line. Har student ke baad: form total = voucher = profile Fee card? Pehle student banao, phir neeche ke checks karte jao.

CHHOTE VERIFY (pehle)
L1 Structure title mein 170+ character type karo: counter "N/200" dikhe (Create + Edit).
L2 Admission > Apply Discount > Percentage mein 400 type karo: field ke neeche turant "Percentage can be 0 to 100 only."
V1 Sibling: phone se existing guardian -> email auto-bhari readonly -> submit ho jaye.
V2 Urdu guardian naam (محمد اکرم) + Urdu student naam save ho; "Ali 123" reject.
V3 Structure item mein 1234.567: form 2 decimal par rok/ "Amount can have at most 2 decimal places."
V4 Voucher Generate (Nursery-A): structure card dikhe, "No Fee Structure Found" NAHI, "Not in this voucher" note, optional alag.
V6 Discount wale student ke profile top par net Monthly Fee, structure rate chhota.
V8 Payment reversal khali reason: inline "Please write the reason."
V15 Band (inactive) structure delete: title "Cannot delete" + saaf message; active par "Make it Inactive" ek click.

ADMISSION SCENARIOS
A-8b Fixed discount: Apply Discount -> type -> Discount In = Fixed (Rs.) -> 500 -> Monthly Fee tick -> reason. Preview "Rs 5,000 - Rs 500 = Rs 4,500"? Fee se zyada amount par submit ruke? Admit karke edit kholo: type/value/reason wapas? Profile Fee card + NAYA voucher: original, discount, net (F7).
A-8c Sibling discount (do bachay ek guardian). A-8d dobara discount.
A-9  Fee Heads (manual) mode: 0 amount, head hata do, custom amount keyboard se, reason.
A-10 Structure uthao phir ek amount badlo: "custom" label/reason.
A-13 Mid-year admission (date session ke beech): pichle mahine ki fee? Aug-Jun item ka asar.
A-3  Johar Town (T-6) ya All-campus class mein ek student (campus switch kar ke).
A-14 Double-click submit (do student to nahi bane?), lamba naam, form beech mein refresh.

AAKHIR: tables + LOG BLOCK + DATA CREATED + CONFUSING + ek line "Fee structure aur admission SATISFIED / NOT SATISFIED, kyun" (NOT ho to top 3 items).
Context khatam hone wala ho to pehle ab tak ke tables do.
```
