<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';

interface Ledger {
    id: number;
    ledger_number: string;
    transaction_type: string;
    amount: number;
    description: string;
    category?: { name: string };
    payment_method: string;
    student?: { name: string };
    supplier?: { name: string };
}

interface Props {
    date?: string;
    transactions?: Ledger[];
    totalIncome?: number;
    totalExpense?: number;
    balance?: number;
    campuses?: Array<{ id: number; name: string }>;
    selectedCampus?: number;
}

const props = defineProps<Props>();

// Computed
const transactions = computed(() => props.transactions || []);
const totalIncome = computed(() => props.totalIncome || 0);
const totalExpense = computed(() => props.totalExpense || 0);
const balance = computed(() => props.balance || 0);
const campuses = computed(() => props.campuses || []);

// Form state
const date = ref(props.date || new Date().toISOString().split('T')[0]);
const selectedCampus = ref(props.selectedCampus);

// Breadcrumbs
const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Finance', href: '/finance' },
    { title: 'Reports', href: '/finance/reports' },
    { title: 'Cash Book' },
];

// Format currency
const formatMoney = (amount: number) => {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0,
    }).format(amount);
};

// Load report
const loadReport = () => {
    const params: Record<string, string> = { date: date.value };
    if (selectedCampus.value) params.campus_id = String(selectedCampus.value);
    router.get('/finance/reports/cash-book', params);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Cash Book" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-foreground">
                    Cash Book
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Daily cash transactions report
                </p>
            </div>

            <!-- Filters -->
            <div class="bg-card rounded-lg border border-border p-4">
                <div class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Date</label>
                        <input type="date" v-model="date" class="rounded-md border-border">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Campus</label>
                        <select v-model="selectedCampus" class="rounded-md border-border">
                            <option :value="undefined">All Campuses</option>
                            <option v-for="campus in campuses" :key="campus.id" :value="campus.id">{{ campus.name }}</option>
                        </select>
                    </div>
                    <Button @click="loadReport">Load Report</Button>
                </div>
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-success/10 rounded-lg p-4">
                    <p class="text-sm text-muted-foreground">Total Income</p>
                    <p class="text-xl font-bold text-success">{{ formatMoney(totalIncome) }}</p>
                </div>
                <div class="bg-destructive/10 rounded-lg p-4">
                    <p class="text-sm text-muted-foreground">Total Expense</p>
                    <p class="text-xl font-bold text-destructive">{{ formatMoney(totalExpense) }}</p>
                </div>
                <div class="bg-primary/10 rounded-lg p-4">
                    <p class="text-sm text-muted-foreground">Net Balance</p>
                    <p class="text-xl font-bold" :class="balance >= 0 ? 'text-success' : 'text-destructive'">{{ formatMoney(balance) }}</p>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="bg-card rounded-lg border border-border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Ledger #</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Category</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Description</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="txn in transactions" :key="txn.id" class="hover:bg-accent">
                                <td class="px-4 py-3 text-sm">{{ txn.ledger_number }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span :class="txn.transaction_type === 'INCOME' ? 'bg-success/10 text-success' : 'bg-destructive/10 text-destructive'" class="px-2 py-1 text-xs rounded-full">
                                        {{ txn.transaction_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">{{ txn.category?.name || '-' }}</td>
                                <td class="px-4 py-3 text-sm max-w-xs truncate">{{ txn.description || '-' }}</td>
                                <td class="px-4 py-3 text-sm text-right font-medium" :class="txn.transaction_type === 'INCOME' ? 'text-success' : 'text-destructive'">
                                    {{ txn.transaction_type === 'INCOME' ? '+' : '-' }}{{ formatMoney(txn.amount) }}
                                </td>
                            </tr>
                            <tr v-if="transactions.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">No transactions for this date</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
