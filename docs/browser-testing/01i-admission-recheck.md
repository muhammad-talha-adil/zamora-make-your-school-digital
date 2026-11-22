# 01i · Admission: 01h fixes re-check + baaki scenarios

Same browser chat mein paste karein. Ctrl+Shift+R. Login pehle se hai to logout/login MAT karo.

```
FOCUS: sirf Student Admission + voucher + structure list. Baaqi kuch nahi. DB reset/delete nahi. Naye students "T-<number> ...". "T-4 Class 6 huge amount" delete mat karo. MASTER PROMPT P1-P10.
Har item PASS/FAIL + ek line. Har student ke baad: form total = voucher = profile Fee card?

RE-CHECK (01h fixes)
G-B  Fixed discount: Nursery-A mein naya student, Apply Discount -> type "Other Discount" -> Discount In = Fixed (Rs.) -> 500 -> reason. Submit ho jaye? Profile Fee card: original/discount/net? Edit kholo: type/value/reason wapas aayein? Naya voucher: original, discount, net.
G-A  Transport wale structure (optional/transport item) par student bina transport: profile Monthly Fee = voucher tuition total (transport shamil nahi)? Purana "T Eight" student bhi dekho.
G-C  Manual (Fee Heads) mode: amount 0 ya badli hui amount + khali reason -> submit ruke, "Please write the reason." Reason likhne par ho jaye.
G-D  Student profile se "Generate first voucher": form prefilled + structure card dikhe (No Fee Structure Found NAHI).
G-E  Admission submit par double-click: ek hi student, jhoota "admission number already taken" nahi.
G-F  Voucher due date: August ka voucher aaj (Nov) generate karo -> due aaj ki date; agle mahine ka voucher -> us mahine ki 10 tareekh. Theek lagta hai?
G-G  Inactive structure (T-1) delete: "already Inactive" wala message, "Make it Inactive" button nahi. Active structure par button ek click mein.
UI   Structure list: Class aur Section alag seedhi columns mein, badge ki jagah har row mein same?

BAAQI SCENARIOS
A-3  Johar Town (T-6) ya All-campus class mein ek student (campus switch karke).
A-8c Sibling discount: ek guardian ke do bachay, dusre par discount. A-8d: dobara discount lagana.
A-14 Bohot lamba student naam (200+), form beech mein refresh (data kya hota hai?).
A-9  Manual mode se head hata do, custom amount keyboard se.

AAKHIR: tables + LOG BLOCK + DATA CREATED + CONFUSING + ek line "Fee structure aur admission SATISFIED / NOT SATISFIED, kyun" (NOT ho to top 3 items).
Context khatam hone wala ho to pehle ab tak ke tables do.
```
