<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { BreadcrumbItem } from '@/types';

interface TransactionRow {
    id: string;
    system: 'ledger' | 'journal';
    module: string;
    kind: string;
    reference_no: string;
    transaction_date: string;
    description: string;
    campus_name?: string | null;
    student_name?: string | null;
    counterparty_name?: string | null;
    category_name?: string | null;
    status: string;
    amount: number;
    detail_url?: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface TransactionPaginator {
    data: TransactionRow[];
    links: PaginationLink[];
    from?: number | null;
    to?: number | null;
    total: number;
}

interface Filters {
    campus_id?: number | string;
    system?: string;
    module?: string;
    kind?: string;
    date_from?: string;
    date_to?: string;
    search?: string;
}

interface Props {
    transactions: TransactionPaginator;
    campuses: Array<{ id: number; name: string }>;
    filters?: Filters;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Finance', href: '/finance' },
    { title: 'Transactions', href: '/finance/transactions' },
];

const filters = ref<Filters>({
    campus_id: props.filters?.campus_id ?? '',
    system: props.filters?.system ?? '',
    module: props.filters?.module ?? '',
    kind: props.filters?.kind ?? '',
    date_from: props.filters?.date_from ?? '',
    date_to: props.filters?.date_to ?? '',
    search: props.filters?.search ?? '',
});

const transactions = computed(() => props.transactions?.data ?? []);
const paginationLinks = computed(() => props.transactions?.links ?? []);
const campuses = computed(() => props.campuses ?? []);

const formatMoney = (amount: number | null | undefined) => {
    if (amount === null || amount === undefined) return 'Rs 0';

    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (date: string) => {
    if (!date) return '-';

    return new Date(date).toLocaleDateString('en-PK', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getKindClasses = (kind: string) => {
    switch (kind) {
        case 'income':
            return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300';
        case 'expense':
            return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300';
        case 'collection':
            return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300';
        case 'adjustment':
            return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300';
        default:
            return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300';
    }
};

const getSystemClasses = (system: string) => {
    return system === 'journal'
        ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900'
        : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200';
};

const applyFilters = () => {
    const params: Record<string, string> = {};

    if (filters.value.campus_id) params.campus_id = String(filters.value.campus_id);
    if (filters.value.system) params.system = filters.value.system;
    if (filters.value.module) params.module = filters.value.module;
    if (filters.value.kind) params.kind = filters.value.kind;
    if (filters.value.date_from) params.date_from = filters.value.date_from;
    if (filters.value.date_to) params.date_to = filters.value.date_to;
    if (filters.value.search) params.search = filters.value.search;

    router.get('/finance/transactions', params, { preserveState: true, replace: true });
};

const resetFilters = () => {
    filters.value = {
        campus_id: '',
        system: '',
        module: '',
        kind: '',
        date_from: '',
        date_to: '',
        search: '',
    };

    router.get('/finance/transactions', {}, { replace: true });
};

const openPage = (url: string | null) => {
    if (!url) return;
    router.visit(url, { preserveScroll: true, preserveState: true });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Finance Transactions" />

        <div class="space-y-6 p-4 md:p-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Finance Transactions
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Combined view of posted journals and direct finance ledger activity.
                    </p>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 md:p-6">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Campus</label>
                        <select v-model="filters.campus_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                            <option value="">All Campuses</option>
                            <option v-for="campus in campuses" :key="campus.id" :value="campus.id">
                                {{ campus.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">System</label>
                        <select v-model="filters.system" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                            <option value="">All Systems</option>
                            <option value="journal">Journals</option>
                            <option value="ledger">Ledger</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Module</label>
                        <select v-model="filters.module" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                            <option value="">All Modules</option>
                            <option value="fee">Fee</option>
                            <option value="inventory">Inventory</option>
                            <option value="transport">Transport</option>
                            <option value="staff">Staff</option>
                            <option value="finance">Finance</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Kind</label>
                        <select v-model="filters.kind" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                            <option value="">All Kinds</option>
                            <option value="receivable">Receivable</option>
                            <option value="collection">Collection</option>
                            <option value="adjustment">Adjustment</option>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">From Date</label>
                        <Input v-model="filters.date_from" type="date" />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">To Date</label>
                        <Input v-model="filters.date_to" type="date" />
                    </div>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-[minmax(0,1fr)_auto_auto] md:items-end">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                        <Input
                            v-model="filters.search"
                            placeholder="Search by ref no, description, student, supplier..."
                        />
                    </div>
                    <Button class="h-10" @click="applyFilters">Load Transactions</Button>
                    <Button variant="outline" class="h-10" @click="resetFilters">Reset</Button>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/70">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Date</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Reference</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">System</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Module / Kind</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Student / Counterparty</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Description</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="transaction in transactions"
                                :key="transaction.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-900/40"
                            >
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-200">
                                    {{ formatDate(transaction.transaction_date) }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ transaction.reference_no }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ transaction.category_name || '-' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize', getSystemClasses(transaction.system)]">
                                        {{ transaction.system }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium capitalize text-gray-900 dark:text-white">{{ transaction.module }}</div>
                                    <span :class="['mt-1 inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize', getKindClasses(transaction.kind)]">
                                        {{ transaction.kind }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    <div>{{ transaction.student_name || 'No student linked' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ transaction.counterparty_name || transaction.campus_name || '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ transaction.description }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ formatMoney(transaction.amount) }}
                                </td>
                            </tr>
                            <tr v-if="transactions.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No finance transactions matched the current filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-4 border-t border-gray-200 px-6 py-4 text-sm dark:border-gray-700 md:flex-row md:items-center md:justify-between">
                    <p class="text-gray-600 dark:text-gray-300">
                        Showing {{ props.transactions.from ?? 0 }} to {{ props.transactions.to ?? 0 }} of {{ props.transactions.total }} entries
                    </p>
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            v-for="link in paginationLinks"
                            :key="link.label"
                            type="button"
                            :disabled="!link.url"
                            @click="openPage(link.url)"
                            v-html="link.label"
                            :class="[
                                'rounded-lg border px-3 py-2 text-sm transition',
                                link.active
                                    ? 'border-blue-600 bg-blue-600 text-white'
                                    : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800',
                            ]"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
