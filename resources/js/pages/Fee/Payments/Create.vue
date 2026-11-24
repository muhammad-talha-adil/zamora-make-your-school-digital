<script setup lang="ts">
const moduleLabel = (module: string | null | undefined): string => ({ inventory: 'Inventory', transport: 'Transport' })[module ?? ''] ?? 'Fee';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import Icon from '@/components/Icon.vue';
import { alert, formatCurrency } from '@/utils';
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';

interface StudentSearchResult {
    id: number;
    name: string;
    registration_number: string;
    admission_no?: string | null;
    class_name?: string | null;
    matched_voucher_no?: string | null;
}

interface Voucher {
    id: number;
    voucher_no: string;
    voucher_month: { name: string } | null;
    voucher_year: number;
    net_amount: number;
    balance_amount: number;
    items: VoucherDueItem[];
}

interface VoucherDueItem {
    id: number;
    student_account_charge_id: number | null;
    fee_head_id: number | null;
    fee_head_name?: string | null;
    description: string;
    source_module: string;
    source_type: string;
    amount: number;
    net_amount: number;
    balance_amount: number;
    status: string;
}

interface ChargeAllocation {
    voucher_id: number;
    fee_voucher_item_id: number;
    student_account_charge_id: number | null;
    source_module: string;
    amount: number;
    enabled: boolean;
}

interface Props {
    unpaidVouchers: Voucher[];
    studentId: number | null;
    preselectedVoucherId?: number | null;
    selectedStudent?: StudentSearchResult | null;
    carriedForwardNotice?: string | null;
    carriedForwardTo?: { id: number; voucher_no: string } | null;
}

interface SiblingPayment {
    id: number;
    name: string;
    registration_number: string;
    included: boolean;
    loading: boolean;
    vouchers: Voucher[];
    charges: ChargeAllocation[];
    received_amount: number;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Payments', href: '/fee/payments' },
    { title: 'New Payment', href: '#' },
];

const paymentMethods = [
    { value: 'cash', label: 'Cash' },
    { value: 'bank', label: 'Bank Transfer' },
    { value: 'online', label: 'Online Payment' },
    { value: 'jazzcash', label: 'JazzCash' },
    { value: 'easypaisa', label: 'EasyPaisa' },
    { value: 'cheque', label: 'Cheque' },
];

const paymentMethodOptions = paymentMethods.map((method) => ({ value: method.value, label: method.label }));

const availableVouchers = ref<Voucher[]>(props.unpaidVouchers || []);
const searchQuery = ref(props.selectedStudent?.name || '');
const searchResults = ref<StudentSearchResult[]>([]);
const isSearching = ref(false);
const isLoadingVouchers = ref(false);
const isSubmitting = ref(false);
const showSubmitError = (errors: Record<string, string>) => {
    submitError.value = String(Object.values(errors)[0] ?? 'Could not record the payment.');
    alert.error(submitError.value);
};
const serverError = (field: string): string => {
    const errors = (usePage().props.errors ?? {}) as Record<string, string>;

    return errors[field] ?? '';
};
const notice = computed(() => props.carriedForwardNotice ?? '');
const noticeVoucher = computed(() => props.carriedForwardTo ?? null);

const page = usePage();
const paymentDateError = computed(() => {
    const errors = (page.props.errors ?? {}) as Record<string, string>;
    const key = Object.keys(errors).find((name) => name.endsWith('payment_date'));

    return key ? errors[key] : '';
});
const now = new Date();
const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
const selectedStudent = ref<StudentSearchResult | null>(props.selectedStudent || null);
const futureDateMessage = computed(() => (form.payment_date > today ? 'Date cannot be in the future.' : ''));
const submitError = ref('');
const submitAttempted = ref(false);
const walletConfirmed = ref(false);
const MAX_AMOUNT = 10000000;

const chargeErrors = computed(() => {
    const errors: Record<string, string> = {};

    form.charges.filter((charge) => charge.enabled).forEach((charge) => {
        const key = `${charge.voucher_id}-${charge.fee_voucher_item_id}`;
        const amount = Number(charge.amount);
        const due = itemBalance(charge.voucher_id, charge.fee_voucher_item_id);

        if (!Number.isFinite(amount)) {
            errors[key] = 'Please enter a valid amount.';
        } else if (amount < 0) {
            errors[key] = 'Amount cannot be negative.';
        } else if (amount === 0) {
            errors[key] = 'Please enter the amount to pay.';
        } else if (amount > due) {
            errors[key] = `Amount is more than what is due (Rs ${due.toLocaleString()}).`;
        }
    });

    return errors;
});

const lateFeeError = computed(() => (Number(form.late_fee) < 0 ? 'Late fee cannot be negative.' : ''));
const receivedTouched = ref(false);
const showAmountError = computed(() => Boolean(form.student_id) && (submitAttempted.value || receivedTouched.value));
const rawReceivedError = computed(() => {
    const amount = Number(form.received_amount);

    if (!Number.isFinite(amount) || amount < 0) {
        return 'Amount cannot be negative.';
    }

    if (amount === 0) {
        return 'Please enter the amount received.';
    }

    if (amount > MAX_AMOUNT) {
        return `Amount is too large (max Rs ${MAX_AMOUNT.toLocaleString()}).`;
    }

    return '';
});
const receivedAmountError = computed(() => (showAmountError.value ? rawReceivedError.value : ''));
const methodError = computed(() => (form.payment_method ? '' : 'Please choose how the money was received.'));
const concessionReasonError = computed(() => (Number(form.concession) > 0 && !form.concession_reason.trim() ? 'Please give a reason for the concession.' : ''));
const walletWarning = computed(() => (walletExtra.value > 0.005 ? `This will add Rs ${walletExtra.value.toLocaleString()} to wallet credit.` : ''));
const canSubmit = computed(() => !isSubmitting.value
    && !rawReceivedError.value
    && !futureDateMessage.value
    && !(walletWarning.value && !walletConfirmed.value));

// Sibling bulk payment (#115): let the cashier settle several siblings'
// vouchers in one go instead of repeating this whole form per child.
const siblings = ref<SiblingPayment[]>([]);
const isLoadingSiblings = ref(false);

const form = reactive({
    late_fee: '' as string | number,
    concession: '' as string | number,
    concession_reason: '',
    student_id: props.studentId || '',
    payment_date: today,
    payment_method: 'cash',
    reference_no: '',
    bank_name: '',
    received_amount: 0,
    charges: [] as ChargeAllocation[],
    remarks: '',
});

const initializeVouchers = (vouchers: Voucher[]) => {
    availableVouchers.value = vouchers;
    form.charges = vouchers.flatMap((voucher) =>
        (voucher.items || []).map((item) => ({
            voucher_id: voucher.id,
            fee_voucher_item_id: item.id,
            student_account_charge_id: item.student_account_charge_id,
            source_module: item.source_module || 'fee',
            amount: !props.preselectedVoucherId || props.preselectedVoucherId === voucher.id ? Number(item.balance_amount) : 0,
            enabled: !props.preselectedVoucherId || props.preselectedVoucherId === voucher.id,
        }))
    );
};

initializeVouchers(props.unpaidVouchers || []);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const searchStudents = () => {
    if (searchQuery.value.trim().length < 2) {
        searchResults.value = [];
        return;
    }

    isSearching.value = true;

    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = window.setTimeout(() => {
        axios.get(route('fee.payments.search-students'), {
            params: { q: searchQuery.value.trim() },
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).then((response) => {
            searchResults.value = response.data || [];
        }).catch(() => {
            searchResults.value = [];
        }).finally(() => {
            isSearching.value = false;
        });
    }, 250);
};

const fetchUnpaidVouchers = (studentId: number) => {
    isLoadingVouchers.value = true;

    axios.get(route('fee.vouchers.unpaid'), {
        params: { student_id: studentId },
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    }).then((response) => {
        initializeVouchers(response.data || []);
    }).catch(() => {
        initializeVouchers([]);
        alert.error('Failed to load unpaid vouchers for the selected student.');
    }).finally(() => {
        isLoadingVouchers.value = false;
    });
};

const fetchSiblings = (studentId: number) => {
    isLoadingSiblings.value = true;

    axios.get(route('fee.payments.siblings'), {
        params: { student_id: studentId },
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    }).then((response) => {
        siblings.value = (response.data || []).map((sibling: { id: number; name: string; registration_number: string }) => ({
            ...sibling,
            included: false,
            loading: false,
            vouchers: [],
            charges: [],
            received_amount: 0,
        }));
    }).catch(() => {
        siblings.value = [];
    }).finally(() => {
        isLoadingSiblings.value = false;
    });
};

if (props.studentId) {
    fetchSiblings(props.studentId);
}

const selectStudent = (student: StudentSearchResult) => {
    selectedStudent.value = student;
    form.student_id = student.id;
    searchQuery.value = student.name;
    searchResults.value = [];
    siblings.value = [];
    fetchUnpaidVouchers(student.id);
    fetchSiblings(student.id);
};

const toggleSibling = (sibling: SiblingPayment) => {
    sibling.included = !sibling.included;

    if (sibling.included && sibling.vouchers.length === 0 && !sibling.loading) {
        sibling.loading = true;

        axios.get(route('fee.vouchers.unpaid'), {
            params: { student_id: sibling.id },
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        }).then((response) => {
            const vouchers: Voucher[] = response.data || [];
            sibling.vouchers = vouchers;
            sibling.charges = vouchers.flatMap((voucher) =>
                (voucher.items || []).map((item) => ({
                    voucher_id: voucher.id,
                    fee_voucher_item_id: item.id,
                    student_account_charge_id: item.student_account_charge_id,
                    source_module: item.source_module || 'fee',
                    amount: Number(item.balance_amount),
                    enabled: true,
                }))
            );
            sibling.received_amount = sibling.charges.reduce((sum, c) => sum + Number(c.amount || 0), 0);
        }).catch(() => {
            alert.error(`Failed to load unpaid vouchers for ${sibling.name}.`);
            sibling.included = false;
        }).finally(() => {
            sibling.loading = false;
        });
    }
};

const siblingTotal = (sibling: SiblingPayment) => sibling.charges.reduce((sum, c) => (c.enabled ? sum + Number(c.amount || 0) : sum), 0);

const includedSiblings = computed(() => siblings.value.filter((s) => s.included && s.vouchers.length > 0));

const familyGrandTotal = computed(() =>
    totalAllocated.value + includedSiblings.value.reduce((sum, s) => sum + siblingTotal(s), 0)
);

const getChargeAllocation = (voucherId: number, itemId: number) => {
    return form.charges.find((charge) => charge.voucher_id === voucherId && charge.fee_voucher_item_id === itemId);
};

const toggleCharge = (voucherId: number, item: VoucherDueItem) => {
    const allocation = getChargeAllocation(voucherId, item.id);

    if (!allocation) {
        return;
    }

    allocation.enabled = !allocation.enabled;
    allocation.amount = allocation.enabled ? Number(item.balance_amount) : 0;
};

const itemBalance = (voucherId: number, itemId: number): number => {
    const item = availableVouchers.value
        .find((voucher) => voucher.id === voucherId)
        ?.items.find((due) => due.id === itemId);

    return Number(item?.balance_amount || 0);
};

/**
 * The concession is taken off the selected dues oldest first, the same way
 * the server applies it, so what is left to pay per line is shown here.
 */
const effectiveCharges = computed(() => {
    let remaining = Math.max(0, Number(form.concession) || 0);

    return form.charges
        .filter((charge) => charge.enabled)
        .map((charge) => {
            const take = Math.min(remaining, itemBalance(charge.voucher_id, charge.fee_voucher_item_id));
            remaining = Math.round((remaining - take) * 100) / 100;

            return { ...charge, amount: Math.max(0, Math.round((Number(charge.amount || 0) - take) * 100) / 100) };
        });
});

const selectedBalance = computed(() => form.charges
    .filter((charge) => charge.enabled)
    .reduce((sum, charge) => sum + Math.min(Number(charge.amount || 0), itemBalance(charge.voucher_id, charge.fee_voucher_item_id)), 0));

const concessionError = computed(() => (
    Number(form.concession) > Math.round(selectedBalance.value * 100) / 100
        ? `Concession cannot be more than the balance (Rs ${selectedBalance.value.toLocaleString()}).`
        : ''
));

const totalAllocated = computed(() => effectiveCharges.value.reduce((sum, charge) => sum + charge.amount, 0));

watch([totalAllocated, () => form.late_fee], ([total, lateFee]) => {
    form.received_amount = Math.round((total + (Number(lateFee) || 0)) * 100) / 100;
}, { immediate: true });

const walletExtra = computed(() => Math.round((Number(form.received_amount) - totalAllocated.value - (Number(form.late_fee) || 0)) * 100) / 100);
const remainingAmount = computed(() => Math.max(0, walletExtra.value));

watch(walletExtra, (extra) => {
    if (extra <= 0.005) {
        walletConfirmed.value = false;
    }
});

watch(() => props.studentId, (newId) => {
    if (newId) {
        form.student_id = newId;
    }
});

watch(() => form.payment_method, (method) => {
    if (method === 'cash') {
        form.reference_no = '';
        form.bank_name = '';
    } else if (!['bank', 'cheque'].includes(method)) {
        form.bank_name = '';
    }
});

const submitForm = () => {
    if (isSubmitting.value) {
        return;
    }

    if (!form.student_id) {
        alert.error('Please pick a student first.');
        return;
    }

    const charges = effectiveCharges.value
        .map((charge) => ({
            voucher_id: charge.voucher_id,
            fee_voucher_item_id: charge.fee_voucher_item_id,
            student_account_charge_id: charge.student_account_charge_id,
            source_module: charge.source_module,
            amount: Number(charge.amount),
        }));

    submitAttempted.value = true;

    if (charges.length === 0 || receivedAmountError.value || Object.keys(chargeErrors.value).length > 0 || lateFeeError.value || methodError.value
        || (walletWarning.value && !walletConfirmed.value) || concessionError.value || concessionReasonError.value
        || (form.payment_method !== 'cash' && !form.reference_no.trim()) || futureDateMessage.value) {
        return;
    }

    submitError.value = '';
    isSubmitting.value = true;

    const sharedFields = {
        payment_date: form.payment_date,
        payment_method: form.payment_method,
        reference_no: form.payment_method === 'cash' ? '' : form.reference_no,
        bank_name: ['bank', 'cheque'].includes(form.payment_method) ? form.bank_name : '',
    };

    // If any siblings are included, settle the whole family in one bulk
    // submission - each still becomes its own payment record, but the
    // cashier only fills the form once (#115).
    if (includedSiblings.value.length > 0) {
        const payments = [
            {
                student_id: form.student_id,
                ...sharedFields,
                received_amount: Number(form.received_amount),
                charges,
                remarks: form.remarks,
            },
            ...includedSiblings.value.map((sibling) => ({
                student_id: sibling.id,
                ...sharedFields,
                received_amount: Number(sibling.received_amount),
                charges: sibling.charges
                    .filter((c) => c.enabled && Number(c.amount) > 0)
                    .map((c) => ({
                        voucher_id: c.voucher_id,
                        fee_voucher_item_id: c.fee_voucher_item_id,
                        student_account_charge_id: c.student_account_charge_id,
                        source_module: c.source_module,
                        amount: Number(c.amount),
                    })),
                remarks: form.remarks,
            })),
        ];

        router.post(route('fee.payments.store-bulk'), { payments }, {
            onError: showSubmitError,
            onFinish: () => {
                isSubmitting.value = false;
            },
        });

        return;
    }

    router.post(route('fee.payments.store'), {
        ...form,
        ...sharedFields,
        late_fee: Number(form.late_fee) || 0,
        concession: Number(form.concession) || 0,
        concession_reason: Number(form.concession) > 0 ? form.concession_reason : '',
        confirm_wallet_credit: walletConfirmed.value,
        charges,
    }, {
        onError: showSubmitError,
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Record Payment" />

        <div class="space-y-6 p-4 md:p-6">
            <div>
                <h1 class="text-lg font-bold text-foreground md:text-2xl">
                    Record Fee Payment
                </h1>
                <p class="mt-1 text-xs text-muted-foreground md:text-sm">
                    Record a new fee payment from a student
                </p>
            </div>

            <form novalidate @submit.prevent="submitForm" class="max-w-7xl min-w-0 space-y-6">
                <div v-if="notice" class="rounded-md border border-warning/40 bg-warning/10 px-4 py-3 text-sm text-foreground">
                    {{ notice }}
                    <Link v-if="noticeVoucher" :href="route('fee.vouchers.show', noticeVoucher.id)" class="font-medium underline">Open {{ noticeVoucher.voucher_no }}</Link>
                </div>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                    <div class="md:col-span-2 xl:col-span-4">
                        <Label for="student_search">Student or Voucher No *</Label>
                        <div class="relative">
                            <Input
                                id="student_search"
                                v-model="searchQuery"
                                @input="searchStudents"
                                placeholder="Type student name, reg no or voucher no"
                                autocomplete="off"
                            />
                            <div v-if="isSearching" class="absolute right-3 top-3">
                                <Icon icon="loader" class="h-4 w-4 animate-spin text-muted-foreground" />
                            </div>
                            <div
                                v-if="searchResults.length > 0"
                                class="absolute z-10 mt-1 max-h-72 w-full overflow-auto rounded-md border border-border bg-card shadow-lg"
                            >
                                <button
                                    v-for="student in searchResults"
                                    :key="student.id"
                                    type="button"
                                    @click="selectStudent(student)"
                                    class="w-full px-4 py-3 text-left hover:bg-accent"
                                >
                                    <div class="font-medium text-foreground">{{ student.name }}</div>
                                    <div class="text-sm text-muted-foreground">
                                        {{ student.registration_number }}
                                        <span v-if="student.admission_no"> | {{ student.admission_no }}</span>
                                        <span v-if="student.class_name"> | {{ student.class_name }}</span>
                                    </div>
                                    <div v-if="student.matched_voucher_no" class="text-xs text-primary">
                                        Matched voucher: {{ student.matched_voucher_no }}
                                    </div>
                                </button>
                            </div>
                        </div>
                        <div v-if="selectedStudent" class="mt-2 rounded-md bg-primary/10 px-3 py-2 text-sm text-primary">
                            Selected: {{ selectedStudent.name }} ({{ selectedStudent.registration_number }}<span v-if="selectedStudent.admission_no"> | {{ selectedStudent.admission_no }}</span><span v-if="selectedStudent.class_name"> | {{ selectedStudent.class_name }}</span>)
                        </div>
                        <input v-model="form.student_id" type="hidden" required />
                    </div>

                    <div>
                        <Label for="payment_date">Payment Date *</Label>
                        <Input
                            id="payment_date"
                            v-model="form.payment_date"
                            type="date"
                            required
                        />
                        <p v-if="futureDateMessage || paymentDateError" class="mt-1 text-sm text-destructive">{{ futureDateMessage || paymentDateError }}</p>
                    </div>

                    <div>
                        <Label for="payment_method">Payment Method *</Label>
                        <SearchableSelect
                            id="payment_method"
                            v-model="form.payment_method"
                            :options="paymentMethodOptions"
                            placeholder="Select Payment Method"
                        />
                        <p v-if="methodError" class="mt-1 text-sm text-destructive">{{ methodError }}</p>
                    </div>

                    <div v-if="form.payment_method !== 'cash'">
                        <Label for="reference_no">Reference No *</Label>
                        <Input
                            id="reference_no"
                            v-model="form.reference_no"
                            placeholder="Bank transaction ID, cheque number, etc."
                            required
                        />
                        <p v-if="submitAttempted && !form.reference_no.trim()" class="mt-1 text-sm text-destructive">Please enter the reference number.</p>
                    </div>

                    <div v-if="['bank', 'cheque'].includes(form.payment_method)">
                        <Label for="bank_name">Bank Name *</Label>
                        <Input
                            id="bank_name"
                            v-model="form.bank_name"
                            placeholder="Bank name"
                            required
                        />
                    </div>

                    <div>
                        <Label for="late_fee">Late fee (optional)</Label>
                        <Input id="late_fee" v-model="form.late_fee" type="number" step="0.01" min="0" placeholder="0.00 (optional)" />
                        <p v-if="lateFeeError || serverError('late_fee')" class="mt-1 text-sm text-destructive">{{ lateFeeError || serverError('late_fee') }}</p>
                    </div>

                    <div>
                        <Label for="concession">Concession (optional)</Label>
                        <Input id="concession" v-model="form.concession" type="number" step="0.01" min="0" placeholder="0.00 (optional)" />
                        <p v-if="concessionError || serverError('concession')" class="mt-1 text-sm text-destructive">{{ concessionError || serverError('concession') }}</p>
                    </div>

                    <div v-if="Number(form.concession) > 0" class="md:col-span-2 xl:col-span-4">
                        <Label for="concession_reason">Concession reason *</Label>
                        <Input id="concession_reason" v-model="form.concession_reason" maxlength="255" placeholder="Why is this concession given? (required)" />
                        <p v-if="concessionReasonError || serverError('concession_reason')" class="mt-1 text-sm text-destructive">{{ concessionReasonError || serverError('concession_reason') }}</p>
                    </div>

                    <div class="md:col-span-2 xl:col-span-2">
                        <Label for="received_amount">Received Amount *</Label>
                        <Input
                            id="received_amount"
                            v-model="form.received_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="Enter amount received"
                            @input="receivedTouched = true"
                        />
                        <p v-if="receivedAmountError || serverError('received_amount')" class="mt-1 text-sm text-destructive">{{ receivedAmountError || serverError('received_amount') }}</p>
                        <label v-if="walletWarning" class="mt-2 flex items-start gap-2 rounded-md border border-amber-400 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-900 dark:border-amber-500/60 dark:bg-amber-500/15 dark:text-amber-200">
                            <input v-model="walletConfirmed" type="checkbox" class="mt-1 rounded" />
                            <span>{{ walletWarning }} Tick to confirm.</span>
                        </label>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg border border-border bg-card">
                    <div class="border-b border-border px-6 py-4">
                        <h2 class="text-lg font-semibold text-foreground">Voucher Dues</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Select exactly which fee or inventory dues you want to settle for this student.
                        </p>
                    </div>

                    <div v-if="isLoadingVouchers" class="py-10 text-center text-muted-foreground">
                        Loading unpaid dues...
                    </div>

                    <div v-else-if="availableVouchers.length === 0" class="py-10 text-center text-muted-foreground">
                        No unpaid voucher dues found for the selected student.
                    </div>

                    <div v-else class="divide-y divide-border md:hidden">
                        <template v-for="voucher in availableVouchers" :key="`card-${voucher.id}`">
                            <div class="bg-muted/60 px-4 py-2">
                                <div class="font-medium text-foreground">{{ voucher.voucher_no }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ voucher.voucher_month?.name || 'N/A' }} {{ voucher.voucher_year }} - balance {{ formatCurrency(voucher.balance_amount) }}
                                </div>
                            </div>
                            <div v-for="item in voucher.items" :key="`card-${item.id}`" class="space-y-2 px-4 py-3">
                                <label class="flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        :checked="getChargeAllocation(voucher.id, item.id)?.enabled"
                                        class="mt-1 rounded"
                                        @change="toggleCharge(voucher.id, item)"
                                    />
                                    <span class="min-w-0 flex-1">
                                        <span class="block break-words font-medium text-foreground">{{ item.fee_head_name || item.description }}</span>
                                        <span class="block break-words text-sm text-muted-foreground">{{ item.description }}</span>
                                        <span class="block text-sm text-muted-foreground">Due: {{ formatCurrency(item.balance_amount) }}</span>
                                    </span>
                                </label>
                                <Input
                                    :model-value="String(getChargeAllocation(voucher.id, item.id)?.amount ?? item.balance_amount)"
                                    @update:model-value="(value) => { const allocation = getChargeAllocation(voucher.id, item.id); if (allocation) allocation.amount = Number(value); }"
                                    type="number"
                                    :max="item.balance_amount"
                                    step="0.01"
                                    min="0"
                                    :disabled="!getChargeAllocation(voucher.id, item.id)?.enabled"
                                    class="w-full"
                                    placeholder="Pay amount"
                                />
                                <p v-if="chargeErrors[`${voucher.id}-${item.id}`]" class="text-xs text-destructive">{{ chargeErrors[`${voucher.id}-${item.id}`] }}</p>
                            </div>
                        </template>
                    </div>

                    <div v-if="!isLoadingVouchers && availableVouchers.length > 0" class="hidden overflow-x-auto md:block">
                        <table class="min-w-[520px] w-full table-fixed divide-y divide-border">
                            <colgroup>
<col class="w-12" />
<col class="w-20" />
<col />
<col class="w-28" />
<col class="w-36" />
</colgroup>
<thead class="bg-muted">
                                <tr>
                                    <th class="px-3 py-3 text-center text-xs font-semibold uppercase text-muted-foreground">
                                        Pick
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted-foreground">
                                        Module
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted-foreground">
                                        Due Item
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-muted-foreground">
                                        Due
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-muted-foreground">
                                        Pay Amount
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <template v-for="voucher in availableVouchers" :key="voucher.id">
                                    <tr class="bg-muted/60">
                                        <td colspan="5" class="px-4 py-2">
                                            <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                                                <div class="font-medium text-foreground">
                                                    {{ voucher.voucher_no }}
                                                    <span class="ml-2 text-sm font-normal text-muted-foreground">
                                                        {{ voucher.voucher_month?.name || 'N/A' }} {{ voucher.voucher_year }}
                                                    </span>
                                                </div>
                                                <div class="text-sm text-muted-foreground">
                                                    Voucher balance: {{ formatCurrency(voucher.balance_amount) }}
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="item in voucher.items"
                                        :key="item.id"
                                        class="hover:bg-accent"
                                    >
                                        <td class="px-3 py-3 text-center align-top">
                                            <input
                                                type="checkbox"
                                                :checked="getChargeAllocation(voucher.id, item.id)?.enabled"
                                                class="rounded"
                                                @change="toggleCharge(voucher.id, item)"
                                            />
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            <span
                                                :class="item.source_module === 'inventory' || item.source_module === 'transport'
                                                    ? 'inline-flex rounded-full bg-warning/10 px-2 py-1 text-xs font-medium text-warning'
                                                    : 'inline-flex rounded-full bg-primary/10 px-2 py-1 text-xs font-medium text-primary'"
                                            >
                                                {{ moduleLabel(item.source_module) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            <div class="break-words font-medium text-foreground">
                                                {{ item.fee_head_name || item.description }}
                                            </div>
                                            <div class="break-words text-sm text-muted-foreground">
                                                {{ item.description }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 align-top text-right text-muted-foreground">
                                            {{ formatCurrency(item.balance_amount) }}
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            <Input
                                                :model-value="String(getChargeAllocation(voucher.id, item.id)?.amount ?? item.balance_amount)"
                                                @update:model-value="(value) => { const allocation = getChargeAllocation(voucher.id, item.id); if (allocation) allocation.amount = Number(value); }"
                                                type="number"
                                                :max="item.balance_amount"
                                                step="0.01"
                                                min="0"
                                                :disabled="!getChargeAllocation(voucher.id, item.id)?.enabled"
                                                class="ml-auto w-full max-w-[8rem] text-right"
                                                placeholder="0.00"
                                            />
                                            <p v-if="chargeErrors[`${voucher.id}-${item.id}`]" class="mt-1 text-right text-xs text-destructive">{{ chargeErrors[`${voucher.id}-${item.id}`] }}</p>
                                            <p class="mt-1 text-right text-[11px] text-muted-foreground">
                                                Pay now
                                            </p>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Siblings (#115) - pay for the whole family in one submission -->
                <div v-if="siblings.length > 0" class="overflow-hidden rounded-lg border border-border bg-card">
                    <div class="border-b border-border px-6 py-4">
                        <h2 class="text-lg font-semibold text-foreground">Siblings</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Include a sibling to settle their unpaid vouchers together with this payment.
                        </p>
                    </div>
                    <div v-if="isLoadingSiblings" class="py-6 text-center text-muted-foreground">Loading siblings...</div>
                    <div v-else class="divide-y divide-border">
                        <div v-for="sibling in siblings" :key="sibling.id" class="px-6 py-4">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    :checked="sibling.included"
                                    class="rounded"
                                    @change="toggleSibling(sibling)"
                                />
                                <span class="font-medium text-foreground">{{ sibling.name }}</span>
                                <span class="text-sm text-muted-foreground">{{ sibling.registration_number }}</span>
                                <Icon v-if="sibling.loading" icon="loader" class="h-4 w-4 animate-spin text-muted-foreground" />
                            </label>

                            <div v-if="sibling.included && sibling.vouchers.length > 0" class="mt-3 space-y-2 pl-7">
                                <div v-for="voucher in sibling.vouchers" :key="voucher.id" class="text-sm text-muted-foreground">
                                    {{ voucher.voucher_no }} - {{ voucher.voucher_month?.name || 'N/A' }} {{ voucher.voucher_year }}
                                    (balance {{ formatCurrency(voucher.balance_amount) }})
                                </div>
                                <div>
                                    <Label :for="`sibling-amount-${sibling.id}`">Amount to receive for {{ sibling.name }}</Label>
                                    <Input
                                        :id="`sibling-amount-${sibling.id}`"
                                        v-model.number="sibling.received_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="max-w-xs"
                                    />
                                </div>
                            </div>
                            <p v-else-if="sibling.included && !sibling.loading" class="mt-2 pl-7 text-sm text-muted-foreground">
                                No unpaid vouchers for {{ sibling.name }}.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-border bg-muted p-6">
                    <div class="mb-4 flex flex-wrap gap-2 items-center justify-between">
                        <h2 class="text-lg font-semibold text-foreground">Payment Summary</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-4 text-center md:grid-cols-3">
                        <div>
                            <p class="text-sm text-muted-foreground">Received</p>
                            <p class="text-xl font-bold text-foreground">{{ formatCurrency(form.received_amount) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Allocated</p>
                            <p class="text-xl font-bold text-primary">{{ formatCurrency(totalAllocated) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Wallet Credit</p>
                            <p :class="['text-xl font-bold', remainingAmount > 0 ? 'text-success' : 'text-muted-foreground']">
                                {{ formatCurrency(Math.max(0, remainingAmount)) }}
                            </p>
                        </div>
                    </div>
                    <div v-if="includedSiblings.length > 0" class="mt-4 border-t border-border pt-4 text-center">
                        <p class="text-sm text-muted-foreground">Family Total (this student + {{ includedSiblings.length }} sibling(s))</p>
                        <p class="text-xl font-bold text-foreground">{{ formatCurrency(familyGrandTotal) }}</p>
                    </div>
                </div>

                <div>
                    <Label for="remarks">Remarks</Label>
                    <textarea
                        id="remarks"
                        v-model="form.remarks"
                        rows="3"
                        placeholder="Anything to note about this payment (optional)"
                        class="mt-1 block w-full rounded-md border border-border bg-card px-3 py-2 text-foreground"
                    ></textarea>
                </div>

                <div class="flex flex-wrap justify-end gap-3">
                    <Button type="button" variant="outline" @click="router.visit(route('fee.payments.index'))">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="!canSubmit">
                        <Icon v-if="isSubmitting" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                        {{ isSubmitting ? 'Processing...' : 'Record Payment' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
