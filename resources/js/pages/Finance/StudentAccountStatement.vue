<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import axios from 'axios';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import Icon from '@/components/Icon.vue';
import type { BreadcrumbItem } from '@/types';

interface StudentSummary {
    id: number;
    name: string;
    registration_no: string;
    admission_no?: string | null;
    campus?: string | null;
    session?: string | null;
    class?: string | null;
    section?: string | null;
    wallet_balance: number;
}

interface ChargeRow {
    id: number;
    module: string;
    title: string;
    description?: string | null;
    charge_date?: string | null;
    due_date?: string | null;
    period?: string | null;
    class_name?: string | null;
    section_name?: string | null;
    voucher_no?: string | null;
    amount: number;
    discount_amount: number;
    fine_amount: number;
    net_amount: number;
    paid_amount: number;
    balance_amount: number;
    status: string;
}

interface AdjustmentRow {
    id: number;
    module: string;
    type: string;
    amount: number;
    reason?: string | null;
    date?: string | null;
    voucher_no?: string | null;
    charge_title?: string | null;
}

interface PaymentAllocationRow {
    id: number;
    module: string;
    voucher_no?: string | null;
    charge_title?: string | null;
    amount: number;
}

interface PaymentRow {
    id: number;
    receipt_no: string;
    payment_date?: string | null;
    payment_method: string;
    received_amount: number;
    allocated_amount: number;
    excess_amount: number;
    status: string;
    received_by?: string | null;
    allocations: PaymentAllocationRow[];
}

interface WalletRow {
    id: number;
    date?: string | null;
    type: string;
    direction: string;
    amount: number;
    description?: string | null;
}

interface TimelineRow {
    date?: string | null;
    entry_type: string;
    module: string;
    title: string;
    reference?: string | null;
    debit: number;
    credit: number;
    status: string;
}

interface StatementPayload {
    summary: {
        total_billed: number;
        total_paid: number;
        total_adjustments: number;
        total_outstanding: number;
        fee_outstanding: number;
        inventory_outstanding: number;
        transport_outstanding: number;
        wallet_balance: number;
    };
    charges: ChargeRow[];
    adjustments: AdjustmentRow[];
    payments: PaymentRow[];
    wallet_transactions: WalletRow[];
    timeline: TimelineRow[];
}

interface StudentSearchResult {
    id: number;
    name: string;
    registration_no: string;
    admission_no?: string | null;
    display_text: string;
}

interface Props {
    filters?: { student_id?: number | string };
    student?: StudentSummary | null;
    statement?: StatementPayload | null;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Finance', href: '/finance' },
    { title: 'Student Statement', href: '/finance/student-account-statement' },
];

const searchQuery = ref(props.student?.name || '');
const searchResults = ref<StudentSearchResult[]>([]);
const isSearching = ref(false);

const statement = computed(() => props.statement);
const charges = computed(() => statement.value?.charges ?? []);
const adjustments = computed(() => statement.value?.adjustments ?? []);
const payments = computed(() => statement.value?.payments ?? []);
const walletTransactions = computed(() => statement.value?.wallet_transactions ?? []);
const timeline = computed(() => statement.value?.timeline ?? []);
const summary = computed(() => statement.value?.summary ?? {
    total_billed: 0,
    total_paid: 0,
    total_adjustments: 0,
    total_outstanding: 0,
    fee_outstanding: 0,
    inventory_outstanding: 0,
    transport_outstanding: 0,
    wallet_balance: 0,
});

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const formatMoney = (amount: number | null | undefined) => {
    if (amount === null || amount === undefined) return 'Rs 0';

    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (date: string | null | undefined) => {
    if (!date) return '-';

    return new Date(date).toLocaleDateString('en-PK', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

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
        axios.get(route('finance.student-account-statement.search-students'), {
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

const selectStudent = (student: StudentSearchResult) => {
    searchQuery.value = student.name;
    searchResults.value = [];
    router.get(route('finance.student-account-statement.index'), { student_id: student.id });
};

const resetStatement = () => {
    searchQuery.value = '';
    searchResults.value = [];
    router.get(route('finance.student-account-statement.index'));
};

const getModuleClasses = (module: string) => {
    switch (module) {
        case 'inventory':
            return 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300';
        case 'transport':
            return 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300';
        case 'wallet':
            return 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300';
        default:
            return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300';
    }
};

const getStatusClasses = (status: string) => {
    switch (status) {
        case 'paid':
        case 'posted':
            return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300';
        case 'partial':
            return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300';
        case 'open':
        case 'unpaid':
            return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300';
        default:
            return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Student Account Statement" />

        <div class="space-y-6 p-4 md:p-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Student Account Statement
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    View separated and combined fee, inventory, transport, payment, credit, and wallet records for one student.
                </p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 md:p-6">
                <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_auto_auto] md:items-end">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Search Student</label>
                        <div class="relative">
                            <Input
                                v-model="searchQuery"
                                @input="searchStudents"
                                placeholder="Search by student name, admission no, or registration no..."
                            />
                            <div v-if="isSearching" class="absolute right-3 top-3">
                                <Icon icon="loader" class="h-4 w-4 animate-spin text-gray-400" />
                            </div>
                            <div
                                v-if="searchResults.length > 0"
                                class="absolute z-20 mt-1 max-h-72 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900"
                            >
                                <button
                                    v-for="studentResult in searchResults"
                                    :key="studentResult.id"
                                    type="button"
                                    @click="selectStudent(studentResult)"
                                    class="w-full px-4 py-3 text-left hover:bg-gray-100 dark:hover:bg-gray-800"
                                >
                                    <div class="font-medium text-gray-900 dark:text-white">{{ studentResult.name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ studentResult.registration_no }}
                                        <span v-if="studentResult.admission_no"> | {{ studentResult.admission_no }}</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                    <Button class="h-10" @click="searchStudents">Search</Button>
                    <Button variant="outline" class="h-10" @click="resetStatement">Reset</Button>
                </div>
            </div>

            <div v-if="props.student" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 md:p-6">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Student</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ props.student.name }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ props.student.registration_no }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Campus / Session</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ props.student.campus || '-' }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ props.student.session || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Class / Section</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ props.student.class || '-' }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ props.student.section || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Wallet Balance</p>
                        <p class="text-lg font-semibold text-purple-600">{{ formatMoney(props.student.wallet_balance) }}</p>
                    </div>
                </div>
            </div>

            <div v-if="props.student" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Billed</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ formatMoney(summary.total_billed) }}</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Paid</p>
                    <p class="mt-2 text-2xl font-bold text-green-600">{{ formatMoney(summary.total_paid) }}</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Outstanding</p>
                    <p class="mt-2 text-2xl font-bold text-amber-600">{{ formatMoney(summary.total_outstanding) }}</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Adjustments</p>
                    <p class="mt-2 text-2xl font-bold text-blue-600">{{ formatMoney(summary.total_adjustments) }}</p>
                </div>
            </div>

            <div v-if="props.student" class="grid gap-4 md:grid-cols-4">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Fee Outstanding</p>
                    <p class="mt-2 text-xl font-bold text-blue-600">{{ formatMoney(summary.fee_outstanding) }}</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Inventory Outstanding</p>
                    <p class="mt-2 text-xl font-bold text-orange-600">{{ formatMoney(summary.inventory_outstanding) }}</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Transport Outstanding</p>
                    <p class="mt-2 text-xl font-bold text-indigo-600">{{ formatMoney(summary.transport_outstanding) }}</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Wallet Balance</p>
                    <p class="mt-2 text-xl font-bold text-purple-600">{{ formatMoney(summary.wallet_balance) }}</p>
                </div>
            </div>

            <div v-if="props.student" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Open and Historical Charges</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/70">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Module</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Charge</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Period / Voucher</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Dates</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Net</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Paid</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Balance</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="charge in charges" :key="charge.id">
                                <td class="px-6 py-4">
                                    <span :class="['inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize', getModuleClasses(charge.module)]">
                                        {{ charge.module }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ charge.title }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ charge.description || '-' }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    <div>{{ charge.period || '-' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ charge.voucher_no || 'Not billed in voucher yet' }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    <div>Charge: {{ formatDate(charge.charge_date) }}</div>
                                    <div>Due: {{ formatDate(charge.due_date) }}</div>
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-gray-900 dark:text-white">{{ formatMoney(charge.net_amount) }}</td>
                                <td class="px-6 py-4 text-right text-green-600">{{ formatMoney(charge.paid_amount) }}</td>
                                <td class="px-6 py-4 text-right text-amber-600">{{ formatMoney(charge.balance_amount) }}</td>
                                <td class="px-6 py-4">
                                    <span :class="['inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize', getStatusClasses(charge.status)]">
                                        {{ charge.status }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="charges.length === 0">
                                <td colspan="8" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No charges found for this student yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="props.student" class="grid gap-6 xl:grid-cols-2">
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Payments</h2>
                    </div>
                    <div class="space-y-4 p-6">
                        <div v-for="payment in payments" :key="payment.id" class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ payment.receipt_no }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(payment.payment_date) }} | {{ payment.payment_method }} | {{ payment.received_by || 'System' }}
                                    </div>
                                </div>
                                <span :class="['inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize', getStatusClasses(payment.status)]">
                                    {{ payment.status }}
                                </span>
                            </div>
                            <div class="mt-3 grid gap-2 text-sm text-gray-600 dark:text-gray-300 md:grid-cols-3">
                                <div>Received: <span class="font-medium text-gray-900 dark:text-white">{{ formatMoney(payment.received_amount) }}</span></div>
                                <div>Allocated: <span class="font-medium text-green-600">{{ formatMoney(payment.allocated_amount) }}</span></div>
                                <div>Wallet: <span class="font-medium text-purple-600">{{ formatMoney(payment.excess_amount) }}</span></div>
                            </div>
                            <div v-if="payment.allocations.length > 0" class="mt-4 space-y-2">
                                <div v-for="allocation in payment.allocations" :key="allocation.id" class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm dark:bg-gray-900/40">
                                    <div>
                                        <span :class="['mr-2 inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium capitalize', getModuleClasses(allocation.module)]">
                                            {{ allocation.module }}
                                        </span>
                                        <span class="text-gray-700 dark:text-gray-200">{{ allocation.charge_title || 'Allocated charge' }}</span>
                                        <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">{{ allocation.voucher_no || '-' }}</span>
                                    </div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ formatMoney(allocation.amount) }}</div>
                                </div>
                            </div>
                        </div>
                        <div v-if="payments.length === 0" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            No payments recorded for this student yet.
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Adjustments and Wallet</h2>
                    </div>
                    <div class="space-y-4 p-6">
                        <div v-for="adjustment in adjustments" :key="`adjustment-${adjustment.id}`" class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ adjustment.reason || 'Adjustment' }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(adjustment.date) }} | {{ adjustment.charge_title || 'No linked charge' }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span :class="['inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize', getModuleClasses(adjustment.module)]">
                                        {{ adjustment.module }}
                                    </span>
                                    <div class="mt-2 font-semibold text-blue-600">{{ formatMoney(adjustment.amount) }}</div>
                                </div>
                            </div>
                        </div>

                        <div v-for="walletTransaction in walletTransactions" :key="`wallet-${walletTransaction.id}`" class="rounded-xl border border-dashed border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ walletTransaction.description || 'Wallet transaction' }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(walletTransaction.date) }} | {{ walletTransaction.type }}
                                    </div>
                                </div>
                                <div :class="walletTransaction.direction === 'credit' ? 'text-purple-600' : 'text-amber-600'" class="font-semibold">
                                    {{ walletTransaction.direction === 'credit' ? '+' : '-' }}{{ formatMoney(walletTransaction.amount) }}
                                </div>
                            </div>
                        </div>

                        <div v-if="adjustments.length === 0 && walletTransactions.length === 0" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            No adjustments or wallet activity found for this student.
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="props.student" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Full Timeline</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/70">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Date</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Entry</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Reference</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Debit</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Credit</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="(entry, index) in timeline" :key="`${entry.entry_type}-${entry.reference}-${index}`">
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-200">{{ formatDate(entry.date) }}</td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ entry.title }}</div>
                                    <span :class="['mt-1 inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize', getModuleClasses(entry.module)]">
                                        {{ entry.module }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ entry.reference || '-' }}</td>
                                <td class="px-6 py-4 text-right text-sm font-medium text-red-600">{{ entry.debit ? formatMoney(entry.debit) : '-' }}</td>
                                <td class="px-6 py-4 text-right text-sm font-medium text-green-600">{{ entry.credit ? formatMoney(entry.credit) : '-' }}</td>
                                <td class="px-6 py-4">
                                    <span :class="['inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize', getStatusClasses(entry.status)]">
                                        {{ entry.status }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="timeline.length === 0">
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No statement activity found for this student.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
