/**
 * Fee Module TypeScript Types
 * 
 * Type definitions for fee management
 */

export interface FeeHead {
    id: number;
    name: string;
    code: string;
    category: string;
    description?: string;
    is_recurring: boolean;
    is_optional: boolean;
    is_active: boolean;
    created_at?: string;
    updated_at?: string;
}

export interface FeeStructure {
    id: number;
    title: string;
    session_id: number;
    session?: {
        id: number;
        name: string;
    };
    campus_id: number;
    campus?: {
        id: number;
        name: string;
    };
    class_id?: number;
    class?: {
        id: number;
        name: string;
    };
    section_id?: number;
    section?: {
        id: number;
        name: string;
    };
    status: 'active' | 'inactive' | 'draft';
    effective_from: string;
    effective_to?: string;
    items?: FeeStructureItem[];
    items_count?: number;
    created_at?: string;
    updated_at?: string;
}

export interface FeeStructureItem {
    id: number;
    fee_structure_id: number;
    fee_head_id: number;
    fee_head?: FeeHead;
    amount: number;
    frequency: 'monthly' | 'quarterly' | 'semi_annual' | 'annual' | 'one_time';
    starts_from_month_id?: number;
    starts_from_month?: {
        id: number;
        name: string;
    };
    ends_at_month_id?: number;
    ends_at_month?: {
        id: number;
        name: string;
    };
    is_optional: boolean;
    created_at?: string;
    updated_at?: string;
}

export interface FeeVoucher {
    id: number;
    voucher_no: string;
    student_id: number;
    student?: {
        id: number;
        name: string;
        registration_number: string;
    };
    voucher_month_id: number;
    voucher_month?: {
        id: number;
        name: string;
        number: number;
    };
    voucher_year: number;
    issue_date: string;
    due_date: string;
    status: 'unpaid' | 'partial' | 'paid' | 'overdue' | 'cancelled' | 'adjusted';
    gross_amount: number;
    discount_amount: number;
    fine_amount: number;
    net_amount: number;
    paid_amount: number;
    balance_amount: number;
    items?: FeeVoucherItem[];
    payments?: FeePaymentAllocation[];
    created_at?: string;
    updated_at?: string;
}

export interface FeeVoucherItem {
    id: number;
    fee_voucher_id: number;
    fee_head_id: number;
    fee_head?: FeeHead;
    description: string;
    amount: number;
    discount_amount: number;
    net_amount: number;
    source: 'structure' | 'manual' | 'fine' | 'adjustment';
    created_at?: string;
    updated_at?: string;
}

export interface FeePayment {
    id: number;
    receipt_no: string;
    student_id: number;
    student?: {
        id: number;
        name: string;
        registration_number: string;
    };
    payment_date: string;
    payment_method: 'cash' | 'bank' | 'online' | 'jazzcash' | 'easypaisa' | 'cheque';
    reference_no?: string;
    received_amount: number;
    allocated_amount: number;
    wallet_amount: number;
    status: 'completed' | 'pending' | 'cancelled';
    remarks?: string;
    allocations?: FeePaymentAllocation[];
    created_by?: number;
    created_at?: string;
    updated_at?: string;
}

export interface FeePaymentAllocation {
    id: number;
    fee_payment_id: number;
    fee_voucher_id: number;
    fee_voucher?: FeeVoucher;
    allocated_amount: number;
    created_at?: string;
    updated_at?: string;
}

export interface StudentDiscount {
    id: number;
    student_id: number;
    student?: {
        id: number;
        name: string;
        registration_number: string;
    };
    discount_type_id: number;
    discount_type?: DiscountType;
    value_type: 'fixed' | 'percentage';
    value: number;
    effective_from: string;
    effective_to?: string;
    approval_status: 'pending' | 'approved' | 'rejected';
    approved_by?: number;
    approved_at?: string;
    reason?: string;
    created_at?: string;
    updated_at?: string;
}

export interface DiscountType {
    id: number;
    name: string;
    code: string;
    default_value_type: 'fixed' | 'percentage';
    default_value: number;
    requires_approval: boolean;
    is_active: boolean;
    created_at?: string;
    updated_at?: string;
}

export interface FineRule {
    id: number;
    name: string;
    grace_days: number;
    fine_type: 'fixed' | 'percentage' | 'daily_fixed' | 'daily_percentage';
    fine_value: number;
    max_fine_amount?: number;
    is_active: boolean;
    created_at?: string;
    updated_at?: string;
}

export interface StudentWallet {
    id: number;
    student_id: number;
    student?: {
        id: number;
        name: string;
        registration_number: string;
    };
    balance: number;
    transactions?: WalletTransaction[];
    created_at?: string;
    updated_at?: string;
}

export interface WalletTransaction {
    id: number;
    student_wallet_id: number;
    transaction_type: 'advance_payment' | 'refund' | 'adjustment' | 'fee_payment';
    direction: 'credit' | 'debit';
    amount: number;
    balance_after: number;
    reference_type?: string;
    reference_id?: number;
    description?: string;
    created_at?: string;
    updated_at?: string;
}

export interface Month {
    id: number;
    name: string;
    number: number;
}
