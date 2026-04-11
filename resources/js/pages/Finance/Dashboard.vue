<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';

interface Props {
    today_summary?: {
        income: number;
        expense: number;
        balance: number;
    };
    month_summary?: {
        income: number;
        expense: number;
        balance: number;
    };
    campuses?: Array<{ id: number; name: string }>;
    selected_campus?: number;
}

const props = defineProps<Props>();

// Computed values
const todaySummary = computed(() => props.today_summary || { income: 0, expense: 0, balance: 0 });
const monthSummary = computed(() => props.month_summary || { income: 0, expense: 0, balance: 0 });
const campuses = computed(() => props.campuses || []);

// Form state
const selectedCampus = ref(props.selected_campus);

// Breadcrumbs
const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Finance', href: '/finance' },
    { title: 'Dashboard' },
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

// Change campus
const changeCampus = () => {
    const params: Record<string, string> = {};
    if (selectedCampus.value) params.campus_id = String(selectedCampus.value);
    router.get('/finance', params);
};

// Navigate to route
const navigateTo = (path: string) => {
    router.get(path);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Finance Dashboard" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">
                        Finance Dashboard
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Overview of your financial transactions
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <select v-model="selectedCampus" @change="changeCampus" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        <option :value="undefined">All Campuses</option>
                        <option v-for="campus in campuses" :key="campus.id" :value="campus.id">{{ campus.name }}</option>
                    </select>
                </div>
            </div>

            <!-- Today's Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Today's Income</p>
                    <p class="text-2xl font-bold text-green-600">{{ formatMoney(todaySummary.income) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Today's Expense</p>
                    <p class="text-2xl font-bold text-red-600">{{ formatMoney(todaySummary.expense) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Today's Balance</p>
                    <p class="text-2xl font-bold" :class="todaySummary.balance >= 0 ? 'text-green-600' : 'text-red-600'">
                        {{ formatMoney(todaySummary.balance) }}
                    </p>
                </div>
            </div>

            <!-- Month Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">This Month</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Income</p>
                        <p class="text-xl font-bold text-green-600">{{ formatMoney(monthSummary.income) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Expense</p>
                        <p class="text-xl font-bold text-red-600">{{ formatMoney(monthSummary.expense) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Net Balance</p>
                        <p class="text-xl font-bold" :class="monthSummary.balance >= 0 ? 'text-green-600' : 'text-red-600'">
                            {{ formatMoney(monthSummary.balance) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 md:p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h3>
                    <div class="flex flex-wrap gap-2">
                        <Button @click="navigateTo('/finance/receive-payment')" size="sm">Receive Payment</Button>
                        <Button @click="navigateTo('/finance/make-payment')" size="sm">Make Payment</Button>
                        <Button variant="outline" @click="navigateTo('/finance/transactions')" size="sm">View Transactions</Button>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 md:p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Reports</h3>
                    <div class="flex flex-wrap gap-2">
                        <Button variant="outline" @click="navigateTo('/finance/reports/cash-book')" size="sm">Cash Book</Button>
                        <Button variant="outline" @click="navigateTo('/finance/reports/income')" size="sm">Income Statement</Button>
                        <Button variant="outline" @click="navigateTo('/finance/reports/expense')" size="sm">Expense Statement</Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
