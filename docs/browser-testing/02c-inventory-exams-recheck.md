# 02c · Inventory + Exams re-check

Same browser chat mein paste karein. Ctrl+Shift+R. Login pehle se hai to logout/login MAT karo. (Timetable abhi hide hai, usay test mat karo.)

```
LOGIN: pehle check karo owner login hai ya nahi. Na ho to hi login karo: owner@school.com / 123456. Campus Gulberg. Logout/login baar baar mat karo.
FOCUS: 02b fixes ka re-check (Inventory + Exams). DB reset/delete nahi. Naya data "T-c <number> ...". Staff PORTAL nahi. Window ~1014px, 400px sirf PART 3 mein.
Har item PASS/FAIL + ek line. Na ho sake to "NOT TESTED + wajah". FAIL par exact message/URL. 500 par exact error copy karo.

PART 1 - Inventory
I1 /inventory dashboard ab khulta hai (blank nahi, console error nahi)? Saare submenus theek?
I2 Stock adjustment: qty 0 -> "Quantity must be at least 1."; negative -> same; stock se zyada nikalna -> "Only N in stock."; sahi add/subtract save, toast, Adjustments tab mein entry (date, item, +/- qty, By)?
I3 Stock numbers: Items tab "Current Stock", Stocks tab, Types tab ITEMS count - sab match?
I4 Campus: Suppliers, Student Inventory, Add Type, Create Purchase, item type dropdown mein Campus/"(All Campuses)" nahi? Dropdown mein QA Uniform + T-b Type 1 dono aate hain? Headers "#" hain?
I5 Type: khali submit -> inline "Name is required." + chhota toast (button disabled nahi). Item khali submit -> inline + toast. Naya "T-c Type 1", "T-c Item 1" banao.
I6 Purchase create: Supplier + Date side-by-side overlap nahi; "+" se naya supplier ("T-c Supplier 1") bana sakte hain (Add Supplier button neeche akela nahi)? Khali submit -> per-row inline + toast. Qty -5 -> "Quantity must be at least 1." (5 nahi banta). Sahi purchase -> success toast (bada modal nahi), stock badha?
I7 Delete: T-c Item/Type (unused) delete -> friendly confirm; used item (T-b Item 1) delete -> friendly refusal; type with items delete -> refusal.
I8 Student Inventory: rows mein student ka NAAM (reg no neeche), Returns tab empty state friendly, assign + return flow chalta hai, over-stock par inline message?
I9 Tables 1014px: columns cramped nahi, item naam ek lafz-per-line nahi, tab bars ek row; dates "24 Nov 2026", paise "Rs 1,500".
I10 Header Johar: sirf Johar ka inventory; wapas Gulberg.

PART 2 - Exams
X1 Papers page: saved paper Edit (date/time prefilled) save -> toast, reload par persist, Date Sheet par nayi date? Delete -> friendly dialog (browser prompt nahi). Existing Papers card narrow par kata nahi.
X2 Dashboard Active Exams "15 Dec 2026 to 20 Dec 2026"; papers page "16 Dec 2026 / 09:00 AM". Console mein preSelectedExamId warn nahi.
X3 Admit Cards sirf unhi classes ke jinke papers hain (Nursery/Class 4/5 ke khali cards nahi)?

PART 3 - 400px
Y1 400px: inventory dashboard, purchase create, items&stock, student inventory, papers page: page overflow nahi, buttons clip nahi.

AAKHIR: table (I1-I10, X1-X3, Y1) + LOG BLOCK + DATA CREATED + CONFUSING + "Inventory SATISFIED/NOT + top 3", "Exams SATISFIED/NOT", "Agla module (Finance) ke liye ready? (haan/nahi)".
Context khatam hone wala ho to pehle ab tak ke tables do.
```
