<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';

interface Props {
    summary: {
        total_received: number;
        total_allocated: number;
        total_wallet: number;
        payment_count: number;
    };
    byMethod: Array<{
        payment_method: string;
        total: number;
    }>;
    dailyCollection: Array<{
        date: string;
        total: number;
    }>;
    filters: {
        date_from?: string;
        date_to?: string;
        campus_id?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Reports', href: '/fee/reports' },
    { title: 'Collection Report', href: '/fee/reports/collection' },
];

const form = reactive({
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    campus_id: props.filters.campus_id || '',
});

const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0,
    }).format(amount);
};

const getPaymentMethodLabel = (method: string): string => {
    const labels: Record<string, string> = {
        cash: 'Cash',
        bank: 'Bank Transfer',
        online: 'Online',
        jazzcash: 'JazzCash',
        easypaisa: 'EasyPaisa',
        cheque: 'Cheque',
    };
    return labels[method] || method;
};

const applyFilters = () => {
    router.get(route('fee.reports.collection'), {
        date_from: form.date_from || undefined,
        date_to: form.date_to || undefined,
        campus_id: form.campus_id || undefined,
    });
};

const resetFilters = () => {
    form.date_from = '';
    form.date_to = '';
    form.campus_id = '';
    router.get(route('fee.reports.collection'));
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Collection Report" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">
                    Collection Report
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Fee collection summary and breakdown
                </p>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="grid gap-4 md:grid-cols-4">
                    <div class="space-y-2">
                        <Label for="date_from">Date From</Label>
                        <Input
                            id="date_from"
                            v-model="form.date_from"
                            type="date"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="date_to">Date To</Label>
                        <Input
                            id="date_to"
                            v-model="form.date_to"
                            type="date"
                        />
                    </div>
                    <div class="flex items-end gap-2">
                        <Button @click="applyFilters">Apply Filters</Button>
                        <Button variant="outline" @click="resetFilters">Reset</Button>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Received</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(props.summary.total_received) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Allocated</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ formatCurrency(props.summary.total_allocated) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Wallet/Credit</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ formatCurrency(props.summary.total_wallet) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Transactions</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ props.summary.payment_count }}</p>
                </div>
            </div>

            <!-- Payment Methods Breakdown -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- By Payment Method -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">By Payment Method</h3>
                    <div class="space-y-3">
                        <div v-for="method in props.byMethod" :key="method.payment_method" class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">{{ getPaymentMethodLabel(method.payment_method) }}</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(method.total) }}</span>
                        </div>
                        <div v-if="props.byMethod.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-4">
                            No payments found
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Summary</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Total Received</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(props.summary.total_received) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Allocated to Vouchers</span>
                            <span class="font-medium text-blue-600 dark:text-blue-400">{{ formatCurrency(props.summary.total_allocated) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Added to Wallet</span>
                            <span class="font-medium text-green-600 dark:text-green-400">{{ formatCurrency(props.summary.total_wallet) }}</span>
                        </div>
                        <div class="flex justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                            <span class="font-semibold text-gray-900 dark:text-white">Number of Payments</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ props.summary.payment_count }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Collection Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Daily Collection</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-2 text-gray-500 dark:text-gray-400">Date</th>
                                <th class="text-right py-2 text-gray-500 dark:text-gray-400">Collection</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="day in props.dailyCollection" :key="day.date" class="border-b border-gray-100 dark:border-gray-700">
                                <td class="py-2 text-gray-900 dark:text-white">{{ day.date }}</td>
                                <td class="py-2 text-right text-gray-900 dark:text-white font-medium">{{ formatCurrency(day.total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="props.dailyCollection.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-4">
                        No daily collection data available
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
