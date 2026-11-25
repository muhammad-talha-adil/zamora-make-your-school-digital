# 02d · Inventory + Exams re-check (02c fixes)

Same browser chat mein paste karein. Ctrl+Shift+R. Login pehle se hai to logout/login MAT karo.

```
LOGIN: pehle check karo owner login hai ya nahi. Na ho to hi login karo: owner@school.com / 123456. Campus Gulberg. Logout/login baar baar mat karo.
FOCUS: 02c fixes ka re-check. DB reset/delete nahi. Naya data "T-d <number> ...". Staff PORTAL nahi. Window ~1014px, 400px sirf PART 3 mein.
Har item PASS/FAIL + ek line. Na ho sake to "NOT TESTED + wajah". FAIL par exact message/URL. 500 par exact error copy karo.

PART 1 - Inventory
I1 Delete: unused "T-c Item 1" delete -> hat jata hai? Used "T-b Item 1" delete -> friendly message (exact text likho). Type with items delete -> friendly refusal. Unused "T-c Type 1" (khali karke) delete?
I2 Negative qty: Purchase row mein -5, Adjustment mein -3, Student assign mein -2, Return mein -1: minus rehta hai + inline "Quantity must be at least 1." + toast? Purchase save nahi hota?
I3 Dashboard /inventory: TYPES count = Types tab ke barabar? Low Stock card aur neeche "low stock" list ek jaisi? "By Type" aur "Stock Status" panels mein data aata hai? "Adjust Stock" button Adjustments tab kholta hai?
I4 Create Purchase: naya supplier "T-d Supplier 1" "+" se banao -> dropdown mein foran aaye + selected? Item list mein 0-stock items (T-c Item 1) dikhte hain?
I5 Adjustments tab: "By" column dikhta hai (1014px par bhi)? Purchases list ka "Items" column bhara hai (1014px par)?
I6 Items & Stock Adjustments filter bar: Type aur Item select barabar chaude, Load + Add Adjustment ek row mein? Dashboard stat cards: icon card ke right par, Rs value wali card bhi theek?
I7 Regression: adjustment add/subtract, over-stock "Only N in stock.", student assign + return flow (return bhi chalao), header Johar -> sirf Johar data, wapas Gulberg.

PART 2 - Exams
X1 Paper Edit: tarikh badlo (jaise 16 -> 18 Dec) Save. Reload (Ctrl+Shift+R) ke baad papers page AUR Date Sheet par nayi tarikh? Agar abhi bhi purani: Network tab mein PUT /exam-papers/.. ka request payload aur response status likho.
X2 Papers delete dialog, admit cards, dashboard dates - pehle jaise theek?

PART 3 - 400px
Y1 400px: inventory dashboard, items&stock (adjustments tab), purchase create, papers page: overflow nahi.

AAKHIR: table (I1-I7, X1-X2, Y1) + LOG BLOCK + DATA CREATED + CONFUSING + "Inventory SATISFIED/NOT + top 3", "Exams SATISFIED/NOT", "Agla module (Finance) ke liye ready? (haan/nahi)".
```
