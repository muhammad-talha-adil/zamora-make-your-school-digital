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
    byMethod: Array<{
        payment_method: string;
        count: number;
        total: number;
    }>;
    filters: {
        date_from?: string;
        date_to?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Reports', href: '/fee/reports' },
    { title: 'Payment Methods', href: '/fee/reports/payment-methods' },
];

const form = reactive({
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
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

const getPaymentMethodColor = (method: string): string => {
    const colors: Record<string, string> = {
        cash: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        bank: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        online: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        jazzcash: 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200',
        easypaisa: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200',
        cheque: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    };
    return colors[method] || 'bg-gray-100 text-gray-800';
};

const calculateTotal = (): number => {
    return props.byMethod.reduce((sum, method) => sum + method.total, 0);
};

const calculateCount = (): number => {
    return props.byMethod.reduce((sum, method) => sum + method.count, 0);
};

const applyFilters = () => {
    router.get(route('fee.reports.payment-methods'), {
        date_from: form.date_from || undefined,
        date_to: form.date_to || undefined,
    });
};

const resetFilters = () => {
    form.date_from = '';
    form.date_to = '';
    router.get(route('fee.reports.payment-methods'));
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Payment Methods Report" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">
                    Payment Methods Report
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Fee collection by payment method
                </p>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="grid gap-4 md:grid-cols-3">
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
            <div class="grid gap-4 md:grid-cols-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Amount Collected</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(calculateTotal()) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Transactions</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ calculateCount() }}</p>
                </div>
            </div>

            <!-- Payment Methods Breakdown -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">By Payment Method</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-3 px-4 text-gray-500 dark:text-gray-400">Payment Method</th>
                                <th class="text-right py-3 px-4 text-gray-500 dark:text-gray-400">Transactions</th>
                                <th class="text-right py-3 px-4 text-gray-500 dark:text-gray-400">Amount</th>
                                <th class="text-right py-3 px-4 text-gray-500 dark:text-gray-400">% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="method in props.byMethod" :key="method.payment_method" class="border-b border-gray-100 dark:border-gray-700">
                                <td class="py-3 px-4">
                                    <span :class="['inline-flex items-center px-3 py-1 rounded-full text-sm font-medium', getPaymentMethodColor(method.payment_method)]">
                                        {{ getPaymentMethodLabel(method.payment_method) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right text-gray-900 dark:text-white">{{ method.count }}</td>
                                <td class="py-3 px-4 text-right text-gray-900 dark:text-white font-medium">{{ formatCurrency(method.total) }}</td>
                                <td class="py-3 px-4 text-right text-gray-600 dark:text-gray-300">
                                    {{ calculateTotal() > 0 ? ((method.total / calculateTotal()) * 100).toFixed(1) : 0 }}%
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50 dark:bg-gray-700">
                                <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">Total</td>
                                <td class="py-3 px-4 text-right font-semibold text-gray-900 dark:text-white">{{ calculateCount() }}</td>
                                <td class="py-3 px-4 text-right font-bold text-gray-900 dark:text-white">{{ formatCurrency(calculateTotal()) }}</td>
                                <td class="py-3 px-4 text-right font-semibold text-gray-900 dark:text-white">100%</td>
                            </tr>
                        </tfoot>
                    </table>
                    <div v-if="props.byMethod.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-8">
                        No payment data available for the selected period
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
