# Fee Module Frontend Structure

## Directory Structure

```
resources/js/Pages/Fee/
├── Dashboard/
│   └── Index.vue                    ✅ Created
├── Structures/
│   ├── Index.vue                    (List all fee structures)
│   ├── Create.vue                   (Create new structure)
│   ├── Edit.vue                     (Edit structure)
│   └── Show.vue                     (View structure details)
├── Vouchers/
│   ├── Index.vue                    (List all vouchers)
│   ├── Generate.vue                 (Generate vouchers)
│   ├── Show.vue                     (View voucher details)
│   └── Print.vue                    (Print voucher)
├── Payments/
│   ├── Index.vue                    (List all payments)
│   ├── Create.vue                   (Record new payment)
│   ├── Show.vue                     (View payment details)
│   └── Receipt.vue                  (Print receipt)
├── Discounts/
│   ├── Index.vue                    (List all discounts)
│   ├── Create.vue                   (Create discount)
│   ├── Approval.vue                 (Approve/reject discounts)
│   └── Edit.vue                     (Edit discount)
├── Reports/
│   ├── Index.vue                    (Reports dashboard)
│   ├── Collection.vue               (Collection report)
│   ├── Outstanding.vue              (Outstanding report)
│   └── Defaulters.vue               (Defaulters list)
└── Settings/
    ├── Index.vue                    (Settings dashboard)
    ├── FeeHeads.vue                 (Manage fee heads)
    ├── DiscountTypes.vue            (Manage discount types)
    └── FineRules.vue                (Manage fine rules)
```

## Components Structure

```
resources/js/components/forms/fee/
├── FeeStructureForm.vue             (Fee structure form)
├── FeeStructureItemForm.vue         (Fee structure item form)
├── VoucherGenerateForm.vue          (Voucher generation form)
├── PaymentForm.vue                  (Payment recording form)
├── DiscountForm.vue                 (Discount form)
├── FeeHeadForm.vue                  (Fee head form)
├── DiscountTypeForm.vue             (Discount type form)
└── FineRuleForm.vue                 (Fine rule form)
```

## Menu Structure (Updated in MenuSeeder)

```
Fee Management (Parent)
├── Dashboard
├── Fee Structures
├── Vouchers
├── Payments
├── Discounts
├── Reports
└── Settings
```

## Key Features Per Page

### Dashboard (Index.vue) ✅
- Stats cards (Total vouchers, Unpaid, Overdue, Collected)
- Recent payments list
- Overdue vouchers list
- Quick actions

### Fee Structures
**Index.vue:**
- List all fee structures
- Filter by session, campus, class
- Create new structure button
- Edit/Delete actions
- Activate/Deactivate toggle

**Create.vue:**
- Form to create fee structure
- Add multiple fee items
- Set scope (campus/class/section)
- Set effective dates

**Edit.vue:**
- Edit existing structure
- Modify fee items
- Update scope and dates

**Show.vue:**
- View structure details
- List all fee items
- Show applicable scope
- View history

### Vouchers
**Index.vue:**
- List all vouchers
- Filter by student, month, year, status
- Search by voucher number
- Bulk actions (print, cancel)
- Status badges

**Generate.vue:**
- Select month and year
- Select scope (campus/class/section)
- Preview before generation
- Bulk generate button

**Show.vue:**
- Voucher details
- Line items breakdown
- Payment history
- Print button
- Cancel button (if unpaid)

**Print.vue:**
- Printable voucher format
- Student details
- Fee breakdown
- Payment instructions
- Bank details

### Payments
**Index.vue:**
- List all payments
- Filter by date, student, method
- Search by receipt number
- View receipt button

**Create.vue:**
- Select student
- Show unpaid vouchers
- Select vouchers to pay
- Enter payment details
- Payment method selection
- Calculate totals

**Show.vue:**
- Payment details
- Allocated vouchers
- Receipt information
- Print receipt button

**Receipt.vue:**
- Printable receipt format
- Payment details
- Allocated vouchers
- School letterhead

### Discounts
**Index.vue:**
- List all discounts
- Filter by student, type, status
- Approval status badges
- Approve/Reject buttons

**Create.vue:**
- Select student
- Select discount type
- Enter value (fixed/percent)
- Set effective dates
- Add reason

**Approval.vue:**
- Pending discounts list
- Student details
- Discount details
- Approve/Reject actions
- Bulk approval

**Edit.vue:**
- Edit discount details
- Update value
- Change dates

### Reports
**Index.vue:**
- Reports dashboard
- Quick links to reports
- Date range selector

**Collection.vue:**
- Collection report
- Filter by date, campus, class
- Payment method breakdown
- Export to Excel/PDF

**Outstanding.vue:**
- Outstanding balance report
- By student, class, campus
- Aging analysis
- Export options

**Defaulters.vue:**
- Defaulters list
- Overdue days
- Contact information
- Send reminders

### Settings
**Index.vue:**
- Settings dashboard
- Links to sub-settings

**FeeHeads.vue:**
- List fee heads
- Create/Edit/Delete
- Activate/Deactivate
- Sort order

**DiscountTypes.vue:**
- List discount types
- Create/Edit/Delete
- Set default values

**FineRules.vue:**
- List fine rules
- Create/Edit/Delete
- Set grace days
- Fine calculation type

## Utilities Needed

```typescript
// resources/js/utils/fee.ts
export const formatCurrency = (amount: number): string => {
    return `Rs. ${amount.toLocaleString('en-PK')}`;
};

export const getVoucherStatusColor = (status: string): string => {
    const colors = {
        unpaid: 'yellow',
        partial: 'blue',
        paid: 'green',
        overdue: 'red',
        cancelled: 'gray',
        adjusted: 'purple',
    };
    return colors[status] || 'gray';
};

export const getPaymentMethodLabel = (method: string): string => {
    const labels = {
        cash: 'Cash',
        bank: 'Bank Transfer',
        online: 'Online Payment',
        jazzcash: 'JazzCash',
        easypaisa: 'EasyPaisa',
        cheque: 'Cheque',
    };
    return labels[method] || method;
};
```

## Types Needed

```typescript
// resources/js/types/fee.ts
export interface FeeHead {
    id: number;
    name: string;
    code: string;
    category: string;
    is_recurring: boolean;
    is_optional: boolean;
    is_active: boolean;
}

export interface FeeStructure {
    id: number;
    title: string;
    session_id: number;
    campus_id: number;
    class_id?: number;
    section_id?: number;
    status: string;
    effective_from: string;
    effective_to?: string;
    items?: FeeStructureItem[];
}

export interface FeeStructureItem {
    id: number;
    fee_head_id: number;
    amount: number;
    frequency: string;
    starts_from_month_id?: number;
    ends_at_month_id?: number;
    is_optional: boolean;
}

export interface FeeVoucher {
    id: number;
    voucher_no: string;
    student_id: number;
    voucher_month_id: number;
    voucher_year: number;
    issue_date: string;
    due_date: string;
    status: string;
    gross_amount: number;
    discount_amount: number;
    fine_amount: number;
    net_amount: number;
    balance_amount: number;
    items?: FeeVoucherItem[];
}

export interface FeeVoucherItem {
    id: number;
    fee_head_id: number;
    description: string;
    amount: number;
    discount_amount: number;
    net_amount: number;
}

export interface FeePayment {
    id: number;
    receipt_no: string;
    student_id: number;
    payment_date: string;
    payment_method: string;
    received_amount: number;
    allocated_amount: number;
    status: string;
}

export interface StudentDiscount {
    id: number;
    student_id: number;
    discount_type_id: number;
    value_type: string;
    value: number;
    effective_from: string;
    effective_to?: string;
    approval_status: string;
}
```

## API Endpoints Used

```typescript
// Fee Structures
GET    /fee/structures              - List structures
POST   /fee/structures              - Create structure
GET    /fee/structures/{id}         - Show structure
PUT    /fee/structures/{id}         - Update structure
DELETE /fee/structures/{id}         - Delete structure

// Vouchers
GET    /fee/vouchers                - List vouchers
POST   /fee/vouchers/generate       - Generate vouchers
GET    /fee/vouchers/{id}           - Show voucher
PATCH  /fee/vouchers/{id}/cancel    - Cancel voucher
GET    /fee/vouchers/{id}/print     - Print voucher

// Payments
GET    /fee/payments                - List payments
POST   /fee/payments                - Create payment
GET    /fee/payments/{id}           - Show payment
GET    /fee/payments/{id}/receipt   - Print receipt

// Discounts
GET    /fee/discounts               - List discounts
POST   /fee/discounts               - Create discount
PATCH  /fee/discounts/{id}/approve  - Approve discount
PATCH  /fee/discounts/{id}/reject   - Reject discount

// Reports
GET    /fee/reports/collection      - Collection report
GET    /fee/reports/outstanding     - Outstanding report
GET    /fee/reports/defaulters      - Defaulters report

// Settings
GET    /fee/heads                   - List fee heads
POST   /fee/heads                   - Create fee head
GET    /fee/discount-types          - List discount types
POST   /fee/discount-types          - Create discount type
```

## Implementation Priority

### Phase 1 (High Priority)
1. ✅ Dashboard/Index.vue
2. Structures/Index.vue
3. Structures/Create.vue
4. Vouchers/Index.vue
5. Vouchers/Generate.vue
6. Payments/Index.vue
7. Payments/Create.vue

### Phase 2 (Medium Priority)
8. Structures/Edit.vue
9. Structures/Show.vue
10. Vouchers/Show.vue
11. Vouchers/Print.vue
12. Payments/Show.vue
13. Payments/Receipt.vue

### Phase 3 (Low Priority)
14. Discounts/Index.vue
15. Discounts/Create.vue
16. Discounts/Approval.vue
17. Reports/Index.vue
18. Reports/Collection.vue
19. Reports/Outstanding.vue
20. Reports/Defaulters.vue
21. Settings/Index.vue
22. Settings/FeeHeads.vue
23. Settings/DiscountTypes.vue
24. Settings/FineRules.vue

## Next Steps

1. Create all Vue page files
2. Create form components
3. Create utility functions
4. Create TypeScript types
5. Test each page
6. Add validation
7. Add loading states
8. Add error handling
9. Add success messages
10. Test complete workflow

## Notes

- Follow the same pattern as Exam module
- Use Inertia.js for navigation
- Use Tailwind CSS for styling
- Use shadcn/ui components
- Add proper TypeScript types
- Include loading states
- Add error handling
- Mobile responsive design
- Dark mode support
