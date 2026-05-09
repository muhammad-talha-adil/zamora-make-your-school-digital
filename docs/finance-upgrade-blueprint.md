# Finance Upgrade Blueprint

This project is being upgraded from split module billing toward a unified finance architecture:

1. Operational modules remain separate.
   - Fee
   - Inventory
   - Transport (future)
   - Staff / Payroll (future)

2. Student-facing dues flow into a shared receivable layer.
   - `student_account_charges`
   - `student_account_adjustments`
   - `fee_vouchers`
   - `fee_voucher_items`

3. Institution-wide accounting flows into a shared journal layer.
   - `chart_of_accounts`
   - `journal_entries`
   - `journal_entry_lines`

4. Legacy invoice-based billing has been removed from the active architecture.
   - `invoices`
   - `invoice_items`
   - `payments`
   - `student_fees`
   - `fee_types`

## Current Phase

The current implementation adds the new core schema, links the existing fee-voucher and student-inventory structures into it, and removes the legacy invoice-based billing branch.

## Next Refactor Targets

1. Refactor voucher generation to create and consume `student_account_charges`.
2. Refactor inventory assignment billing to create `student_account_charges`.
3. Refactor student payments and finance posting to generate balanced journal entries.
4. Expand transport, payroll, and other future modules onto the same shared receivable and journal layers.
