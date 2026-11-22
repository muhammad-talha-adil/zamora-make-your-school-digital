# 01e · SIRF 2 cheezein: (1) Fee structure banana (2) Student admission fee scenarios

Same browser chat mein paste karein (MASTER PROMPT pehle se hona chahiye). Logout/login + Ctrl+Shift+R pehle. Sirf owner@school.com / 123456.
Is round mein baaki kuch test NAHI karna (attendance, payroll, exam, transport... sab skip).

```
FOCUS: sirf (1) Fee Structure creation aur (2) Student admission ki fee scenarios. Baaqi koi module mat kholo. DB reset nahi; purana data rakho.
MASTER PROMPT P1-P10 + SPC lagao. Har scenario ka PASS/FAIL + ek line + click count. Ek scenario atke to workaround karke aage badho.
Pehle purana "DUP Class 1 Overlap Test" fee structure delete kar do (allowed). Naye test structures ka naam "T-<number> ..." rakho taake pehchaan ho.

PART 0 · TEEN CHHOTE VERIFY (pehle)
V1 Voucher list se Pay icon: payment page khule (blank nahi). Voucher detail par "Record payment" button?
V2 Fee structure overlap par error normal message mein (raw JSON popup nahi).
V3 Fee head custom amount mein keyboard se 1500 type hota hai.

PART 1 · FEE STRUCTURE: har mumkin combination banao (kam az kam ye 14; naye dimension nazar aaye to wo bhi)
Pehle zaroori fee heads banao agar nahi hain: Tuition (monthly), Admission (once), Annual/Exam (yearly), Transport (monthly, transport-related),
Lab fee (optional), Security deposit (once). Phir:
S1  1 campus + 1 class + 1 section + sirf Tuition monthly                       (sabse simple)
S2  1 campus + 1 class + "All sections" (ek hi baar mein sab sections)          (kya ek record banta hai ya kai? list kaisi dikhti hai?)
S3  1 campus + kai classes ek sath (Nursery, Prep, Class 1) same fees          (multi-class create, kitne clicks)
S4  All Campuses + kai classes (classes ke saath campus label saaf hai? har campus ke liye alag structure banta hai?)
S5  Kai fee heads ek structure mein: Tuition monthly + Admission once + Annual yearly
S6  Item flags: optional item (Lab fee), "admission par lagu" item, transport-related item — har ka asar admission form mein dekhna hai
S7  Frequency mix: monthly + yearly (billing month + year chuno) + once
S8  Part-year item: Tuition August se June tak (start/end month); Annual fee sirf ek mahine (billing month)
S9  Status: draft vs active vs inactive — draft/inactive admission mein nazar na aaye
S10 Default structure (is_default) — kya matlab hai, doosre class par kya hota hai? (confusing ho to likho)
S11 Overlap: same campus+class+section par doosra ACTIVE -> reject. Lekin ek active + ek draft allowed? Draft ko active karte hi reject?
S12 Edit: amount/item badalna, item hatana, class badalna; kya purane vouchers/students par asar aata hai (warning milti hai?)
S13 Zero/negative/bohot bara/decimal amount, khali items, same fee head do baar, khali title, lamba title
S14 Copy/clone ya bar bar naya banane ka tareeqa: agle class ke liye structure banane mein kitne clicks? Behtar tareeqa batao (NEW IDEA).
Har structure ke baad list mein check: kaisa dikhta hai, kitni rows, search/filter, delete (confirm), duplicate ka naam.

PART 2 · STUDENT ADMISSION: har fee scenario (har student ke baad fee review screen + voucher mein dekho ke kya lag raha hai)
Naye students banao (naam khud socho). Har case mein dekho: sahi structure khud aaya? Total sahi? Admission fee sirf ek baar? Optional items?
A-1  Structure se (S1 wala class) — sab default
A-2  All-sections structure wali class mein student (kisi bhi section ka)
A-3  Multi-class / all-campus structure wali class mein student (dono campus mein ek ek)
A-4  Class jis ke liye structure NAHI: kya message aata hai, admission ruk jata hai ya manual chalta hai? Agla kadam saaf hai?
A-5  Ek se zyada structure match (draft/active mix): picker aata hai? kaun sa chuna jata hai?
A-6  Optional item select/deselect (Lab fee) — total badalta hai?
A-7  Transport-related item: student ko route nahi — kya hota hai?
A-8  Discount ke saath: discount type (percentage + fixed + sibling) — total sahi? reason? dobara discount lagana?
A-9  Fee Heads (manual) mode: har head ki amount khud likho; custom amount typing; 0 amount; head hata do; reason
A-10 Structure + manual change ek sath (structure uthao phir ek amount badlo): "custom" ka label/reason?
A-11 Sibling: do bachay ek guardian; dusre par sibling discount khud lagta hai ya manually? Same-last-6-digits wala guardian: warning aati hai?
A-12 Admission ke baad foran voucher: "Generate first voucher" ek click mein? Admission fee + pehle mahine ki tuition sahi?
A-13 Mid-year admission (session beech mein join): pichle mahine ki fee lagti hai ya sirf agle? Part-year item (S8) ka asar.
A-14 Galat data: khali guardian CNIC (sirf name+phone), duplicate CNIC, lamba naam, Urdu naam, future DOB, submit double-click, refresh beech mein.
Har case ke baad student profile > Fee tab / voucher mein wahi numbers aate hain jo form ne dikhaye? Mismatch = High finding.

PART 3 · FEE SCREEN UX (har form par)
Kaun sa field/column/wording faltu ya confusing? Defaults (campus, session, mahina) pehle se bhare? Fee structure se admission tak ka poora raasta kitne clicks?
Naye feature ke idea (NEW IDEA) batao, khud ban jaane wali cheezein (jaise clone structure, class-wise summary).

AAKHIR MEIN: PART 0 table (V1-V3) + S1..S14 table + A-1..A-14 table (har ek PASS/FAIL + ek line) + LOG BLOCK (sirf fee/admission) +
DATA CREATED (structures + students ke naam) + CONFUSING/REMOVE list + NEW IDEAS.
Agar context khatam hone wala ho to pehle ab tak ke tables do aur likho kaun sa scenario baaki hai.
```
