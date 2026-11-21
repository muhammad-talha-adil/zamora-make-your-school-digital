# 01b · Owner: pichle bugs dobara check + setup aage jari (Phase 1 retest, phir Phase 2 se 6)

Bugs fix ho chuke hain aur DB dobara khali (sirf owner) kar di gayi hai. Same browser chat mein (jahan MASTER PROMPT + 01 paste hua tha) yeh paste karein.
Agar nayi chat hai to pehle MASTER PROMPT, phir 01 ka code block, phir yeh.

```
Naya round. DB dobara bilkul khali hai (campuses/sessions/data sab saaf), sirf owner@school.com. Pehle ke 01 ke rules lagao.
Tum wohi asli school owner ho; data khud socho (realistic Pakistani school, kam az kam 2 campuses). Dobara shuru se karo.

EK NAYA RULE: pages aur clicks kam se kam. Har kaam mein note karo: bina wajah page reload/naya page khulna, modal ke baad list ka khud
refresh na hona (manually reload karna pada?), wapas jaane ke extra clicks, bar bar wahi cheez dobara bharna. Ye sab "Efficiency" mein likho:
kaam · ab N clicks/pages · M ho sakte hain · kaise.

PART A · PICHLE BUGS KE FIX VERIFY KARO (pehle yehi, har ek ko PASS/FAIL likho)
S1 Session: owner se naya academic session banao (campus select kiye baghair bhi, aur ek campus select karke bhi). Banane ke FORAN baad bina
   reload table mein dikhe? Sab campuses mein attach hua? "No active session" banner ghayab? Dobara wahi naam -> "already taken" sirf tab
   jab sach mein maujood ho. Campus switcher se har campus par session list/banner check. Session edit/activate/deactivate/delete.
   Session form mein campuses pehle se selected hon (extra click na ho).
S2 Session dates: end < start reject? 2+ saal ka span reject/warn? Start = end?
S3 Dead pages: URL se seedha kholo /settings/campuses/create, /settings/campuses/1/edit, /settings/school-classes/create, /settings/subjects/create,
   /settings/sections/create, /settings/sessions/create, /settings/menus/create, /settings/exam-types — koi 500/blank na ho (list page par
   jana chahiye). Dashboard banner ka "Create Campus" button kahan le jata hai aur modal khulta hai?
S4 School profile phone: "abc", "123", "+92 300 1234567", lambi string, khali — format validation + saaf error message.
S5 Add Campus modal: koi campus type nahi ho to hint dikhta hai? naya type banane ke baad input focus mein rehta hai (ek ke baad doosra
   Enter se)? Pehla campus banane ke clicks (pehle ~12 the).
S7 Empty tables: campuses, classes, sections, subjects, sessions, exam types — khali par helpful message + "Add" action? Chhoti screen par
   Actions column kahin chhupta to nahi?
S8 "No active session" banner: campus hai par session nahi -> har settings page (classes, sections, subjects, fee, students) par dikhta hai?
   Link sahi jagah jata hai? Session banane ke baad ghayab?
S6 Date format: dates dd-mm-yyyy dikhti hain ya mm/dd/yyyy? Pure app mein ek hi format?

PART B · SETUP DOBARA SHURU SE (01 ke Phase 1 se 6, ab bina rukawat)
Phase 1: school profile, campuses (2+), session. Phase 2: classes (single+bulk), sections, subjects, exam types, attendance settings, holidays.
Phase 3: staff (har kirdaar, alag campus), teaching assignments, salary components. Phase 4: fee heads/discounts/fine/structures, students
(siblings, same-last-6-digits guardian, teeno fee modes), ID card, enquiry->admission. Phase 5: attendance, vouchers, payments, exam+result,
timetable (/timetable pehle error deta tha), transport, inventory, finance, payroll+salary slip. Phase 6: poore flow ka review.
Har phase ke baad ek line "PHASE n DONE: pages N, bugs N". Jahan atko wahan ruko mat: workaround se aage badho, bug likho.

AAKHIR MEIN LOG BLOCK + DATA CREATED + SETUP ORDER PROBLEMS (jahan owner atak gaya kyunki app ne agla step nahi bataya) + 
FIX VERIFY TABLE: S1..S8 har ek PASS/FAIL.
```
