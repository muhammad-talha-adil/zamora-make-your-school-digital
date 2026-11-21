<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import TablePagination from '@/components/tables/TablePagination.vue';
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';
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
const campuses = computed(() => props.campuses ?? []);
const campusOptions = computed(() => campuses.value.map((c) => ({ value: c.id, label: c.name })));
const systemOptions = [
    { value: 'journal', label: 'Journals' },
    { value: 'ledger', label: 'Ledger' },
];
const moduleOptions = [
    { value: 'fee', label: 'Fee' },
    { value: 'inventory', label: 'Inventory' },
    { value: 'transport', label: 'Transport' },
    { value: 'staff', label: 'Staff' },
    { value: 'finance', label: 'Finance' },
];
const kindOptions = [
    { value: 'receivable', label: 'Receivable' },
    { value: 'collection', label: 'Collection' },
    { value: 'adjustment', label: 'Adjustment' },
    { value: 'income', label: 'Income' },
    { value: 'expense', label: 'Expense' },
];

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
            return 'bg-success/10 text-success';
        case 'expense':
            return 'bg-destructive/10 text-destructive';
        case 'collection':
            return 'bg-success/10 text-success';
        case 'adjustment':
            return 'bg-warning/10 text-warning';
        default:
            return 'bg-primary/10 text-primary';
    }
};

const getSystemClasses = (system: string) => {
    return system === 'journal'
        ? 'bg-card text-card-foreground'
        : 'bg-muted text-muted-foreground';
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
                    <h1 class="text-2xl font-bold text-foreground">
                        Finance Transactions
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Combined view of posted journals and direct finance ledger activity.
                    </p>
                </div>
            </div>

            <div class="rounded-2xl border border-border bg-card p-4 shadow-sm md:p-6">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">Campus</label>
                        <SearchableSelect
                            v-model="filters.campus_id"
                            :options="campusOptions"
                            placeholder="All Campuses"
                            clearable
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">System</label>
                        <SearchableSelect
                            v-model="filters.system"
                            :options="systemOptions"
                            placeholder="All Systems"
                            clearable
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">Module</label>
                        <SearchableSelect
                            v-model="filters.module"
                            :options="moduleOptions"
                            placeholder="All Modules"
                            clearable
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">Kind</label>
                        <SearchableSelect
                            v-model="filters.kind"
                            :options="kindOptions"
                            placeholder="All Kinds"
                            clearable
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">From Date</label>
                        <Input v-model="filters.date_from" type="date" />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">To Date</label>
                        <Input v-model="filters.date_to" type="date" />
                    </div>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-[minmax(0,1fr)_auto_auto] md:items-end">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">Search</label>
                        <Input
                            v-model="filters.search"
                            placeholder="Search by ref no, description, student, supplier..."
                        />
                    </div>
                    <Button class="h-10" @click="applyFilters">Load Transactions</Button>
                    <Button variant="outline" class="h-10" @click="resetFilters">Reset</Button>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">#</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Date</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Reference</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">System</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Module / Kind</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Student / Counterparty</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Description</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="(transaction, transactionIndex) in transactions"
                                :key="transaction.id"
                                class="hover:bg-accent"
                            >
                                <td class="px-6 py-4 text-sm text-muted-foreground">
                                    {{ ((props.transactions.from || 1) - 1) + transactionIndex + 1 }}
                                </td>
                                <td class="px-6 py-4 text-sm text-muted-foreground">
                                    {{ formatDate(transaction.transaction_date) }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-foreground">{{ transaction.reference_no }}</div>
                                    <div class="text-xs text-muted-foreground">{{ transaction.category_name || '-' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize', getSystemClasses(transaction.system)]">
                                        {{ transaction.system }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium capitalize text-foreground">{{ transaction.module }}</div>
                                    <span :class="['mt-1 inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize', getKindClasses(transaction.kind)]">
                                        {{ transaction.kind }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-muted-foreground">
                                    <div>{{ transaction.student_name || 'No student linked' }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ transaction.counterparty_name || transaction.campus_name || '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-muted-foreground">
                                    {{ transaction.description }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-semibold text-foreground">
                                    {{ formatMoney(transaction.amount) }}
                                </td>
                            </tr>
                            <tr v-if="transactions.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-sm text-muted-foreground">
                                    No finance transactions matched the current filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-border px-6 py-4">
                    <TablePagination
                        :pagination="props.transactions"
                        :show-per-page-selector="false"
                        @page="(_page, url) => openPage(url)"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
