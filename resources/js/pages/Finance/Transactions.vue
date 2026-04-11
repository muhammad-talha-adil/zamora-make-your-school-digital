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
    transaction_date: string;
    amount: number;
    description: string;
    category?: { id: number; name: string };
    campus?: { id: number; name: string };
    student?: { id: number; name: string };
    supplier?: { id: number; name: string };
    payment_method: string;
    created_at: string;
}

interface Props {
    transactions?: { data: Ledger[] };
    campuses?: Array<{ id: number; name: string }>;
    categories?: Array<{ id: number; name: string }>;
    filters?: {
        campus_id?: number;
        type?: string;
        category_id?: number;
        date_from?: string;
        date_to?: string;
        search?: string;
    };
}

const props = defineProps<Props>();

// Computed values
const transactions = computed(() => props.transactions?.data || []);
const campuses = computed(() => props.campuses || []);
const categories = computed(() => props.categories || []);
const filters = ref(props.filters || {
    campus_id: undefined,
    type: '',
    category_id: undefined,
    date_from: '',
    date_to: '',
    search: '',
});

// Breadcrumbs
const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Finance', href: '/finance' },
    { title: 'Transactions' },
];

// Format currency
const formatMoney = (amount: number | null | undefined) => {
    if (amount === null || amount === undefined) return 'Rs 0';
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0,
    }).format(amount);
};

// Format date
const formatDate = (date: string) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-PK');
};

// Get type color
const getTypeColor = (type: string) => {
    if (type === 'INCOME') return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
    return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
};

// Apply filters
const applyFilters = () => {
    const params: Record<string, string> = {};
    if (filters.value.campus_id) params.campus_id = String(filters.value.campus_id);
    if (filters.value.type) params.type = filters.value.type;
    if (filters.value.category_id) params.category_id = String(filters.value.category_id);
    if (filters.value.date_from) params.date_from = filters.value.date_from;
    if (filters.value.date_to) params.date_to = filters.value.date_to;
    if (filters.value.search) params.search = filters.value.search;
    
    router.get('/finance/transactions', params, { replace: true });
};

// Reset filters
const resetFilters = () => {
    filters.value = {
        campus_id: undefined,
        type: '',
        category_id: undefined,
        date_from: '',
        date_to: '',
        search: '',
    };
    router.get('/finance/transactions', {});
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Transactions" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">
                        Transactions
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        View all financial transactions
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3 md:p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Campus</label>
                        <select v-model="filters.campus_id" class="w-full mt-1 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option :value="undefined">All Campuses</option>
                            <option v-for="campus in campuses" :key="campus.id" :value="campus.id">{{ campus.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                        <select v-model="filters.type" class="w-full mt-1 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Types</option>
                            <option value="INCOME">Income</option>
                            <option value="EXPENSE">Expense</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                        <select v-model="filters.category_id" class="w-full mt-1 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option :value="undefined">All Categories</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Date</label>
                        <input type="date" v-model="filters.date_from" class="w-full mt-1 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To Date</label>
                        <input type="date" v-model="filters.date_to" class="w-full mt-1 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="flex items-end gap-2">
                        <Button @click="applyFilters" size="sm">Filter</Button>
                        <Button variant="outline" @click="resetFilters" size="sm">Reset</Button>
                    </div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ledger #</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Category</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Description</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Payment Method</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="transaction in transactions" :key="transaction.id" class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                <td class="px-4 py-3 text-sm">{{ formatDate(transaction.transaction_date) }}</td>
                                <td class="px-4 py-3 text-sm font-medium">{{ transaction.ledger_number }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', getTypeColor(transaction.transaction_type)]">
                                        {{ transaction.transaction_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">{{ transaction.category?.name || '-' }}</td>
                                <td class="px-4 py-3 text-sm max-w-xs truncate">{{ transaction.description || '-' }}</td>
                                <td class="px-4 py-3 text-sm">{{ transaction.payment_method || '-' }}</td>
                                <td class="px-4 py-3 text-sm text-right font-medium" :class="transaction.transaction_type === 'INCOME' ? 'text-green-600' : 'text-red-600'">
                                    {{ transaction.transaction_type === 'INCOME' ? '+' : '-' }}{{ formatMoney(transaction.amount) }}
                                </td>
                            </tr>
                            <tr v-if="transactions.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">No transactions found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
