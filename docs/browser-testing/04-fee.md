# 04 · Fee management

```
MASTER PROMPT (jo is chat mein pehle paste ho chuka) ke rules + SPC har page par lagao. Logins: owner@, accounts@ (campus accountant), principal@ (discount approvals),
clerk@/reception@ (kya access?), student.test@ aur guardian.test@ (apne fees portal par).

PAGES: Fee dashboard, Fee structures (list/create/edit), fee heads, discounts + sibling rules, fine rules/slabs, Vouchers (generate/list/show/
print/challan), Payments (create/list/receipt/reverse), Discount approvals, Reports, student fee ledger.

SCENARIOS
1. FEE STRUCTURE: naya banao — single class, MULTI-class aur multi-campus (naya feature), per-item frequency (monthly/one-time/annual),
   amounts 0/negative/decimal/huge, duplicate structure, edit jab vouchers ban chuke hon, deactivate, copy.
2. DISCOUNTS: percentage vs fixed, sibling concession auto-apply, manual discount ke liye approval (owner/principal approve/reject),
   discount > fee, double discount.
3. FINE: slab rules, late payment par fine ka hisaab, grace days, fine waive.
4. VOUCHER GENERATION: month/class/section select, preview, generate, dobara generate (duplicate roke?), mid-month admission proration, arrears
   carry-forward, discounts, fines, instalments. Generated count message. Kisi student ka structure na ho to kya?
5. VOUCHER SHOW/PRINT/CHALLAN: layout A4, bank challan 3 copy, amounts correct (hand-calc karo 2-3 vouchers), school logo/campus naam, page break,
   dark mode mein print preview.
6. PAYMENTS: poora, partial, overpay, zero, negative, galat date, payment methods, receipt print, ek payment multiple vouchers, same voucher
   dobara pay (paid status), double-click submit, reversal (reason, role-restriction), reversal ke baad voucher status/ledger/finance entry.
7. DASHBOARD/REPORTS: totals vs list se match, date filters, campus filter, collection/defaulters report, export, empty period.
8. PORTAL: student.test@/guardian.test@ -> /portal/fees: apne vouchers, show page, challan print, doosre student ka voucher id URL (403?).
9. CAMPUS SCOPE: accounts@ sirf apne campus ke vouchers/payments; doosray campus ka id URL.
10. FINANCE LINK: payment ke baad Finance transactions mein entry sahi account/amount (Finance module se cross-check).
11. CONSISTENCY: currency Rs. + thousand separators har jagah; date format; status badge colours (paid/unpaid/partial/overdue) har page par same.

EFFICIENCY: ek student ki fee lena kitne clicks (search -> voucher -> pay)? Roll no. se seedha? Barcode/enter shortcut? Bulk voucher print?
Overdue list se direct "pay" action?
```
