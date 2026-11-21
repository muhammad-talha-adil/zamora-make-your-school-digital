# 03 · Students (admission, lifecycle, enquiries, documents)

```
MASTER PROMPT (jo is chat mein pehle paste ho chuka) ke rules + SPC har page par lagao. Logins: owner@ (full), principal@ (campus), reception@ (enquiry/admission), clerk@,
teacher1@ (sirf apni classes, read-mostly), student.test@ (apna record).

PAGES: Students list, Admission form, Student profile/show (tabs), Edit, ID cards, Promotion (/students promotion + undo),
Leaving/Re-admission/Transfer, Leaving certificate, Admission enquiries, Student documents (+ document types),
CSV import/export, Student leaves.

SCENARIOS
1. ADMISSION (sabse ahem): form ke har section: identity, B-Form format, DOB, gender, photo upload, class/section (class chunne par section
   filter hota hai?), guardians (naya + phone se existing guardian dedupe; 2 guardians ke phone ke last 6 digits same — crash nahi hona
   chahiye), address, fee mode (structure se / discount / manual), discount types, sibling link.
   - Required fields khali, galat B-Form, future DOB, duplicate B-Form, duplicate roll no, bohot lambe naam, unicode/Urdu naam.
   - Success: login passwords ek baar dikhe (student + guardian), copy/print option? Refresh par dobara na dikhein.
   - Admit ke baad student list/profile/fee voucher/portal login mein sahi data.
   - Campus admin doosray campus mein admission kar sakta hai? (nahi hona chahiye.)
2. LIST: search (naam/roll/B-Form/phone), filters (campus, class, section, status, session), combos, sort, pagination, per-page, export CSV,
   bulk actions, row actions, status badges. 0 results message. 100+ students (agar kam hon to pagination edge se test).
3. PROFILE: har tab (personal, guardians, fees, attendance, exam, documents, history), edit inline/alag page, photo change, guardian add/remove,
   sibling link/unlink, status badge, print.
4. LIFECYCLE: leaving (reason/date/validation), leaving certificate print (layout, A4, page break), re-admission, transfer (campus/section),
   promotion section-wise: promoted/detained/conditional, preview, confirm, UNDO; promotion ke baad fee/attendance/exam par asar.
5. ID CARDS: select students, preview, print layout, photo missing case, QR/barcode.
6. ENQUIRIES: new enquiry (reception@), follow-up status, convert to admission (data pre-fill?), duplicate phone, filter/search.
7. DOCUMENTS: type banao, upload (pdf/jpg/bara/galat type), view/download/delete, required-document indicator.
8. IMPORT/EXPORT: sample CSV download, sahi import, galat rows (per-row error report), duplicate rows, bara file, export columns sahi.
9. PERMISSIONS: teacher1@ apni class ke ilawa students nahi dekh sakta; student@ ko admin list nahi; id badal kar doosre student ka URL.
10. CONSISTENCY: student ka naam/roll/class label har module (fee, attendance, exam) mein same format.

EFFICIENCY: ek student admit karne mein kitne clicks/fields? Optional sections collapse? Defaults (current session, last used class)?
Sibling admission mein guardian auto-fill? Promotion poori class ke liye kitne clicks?
```
