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
        cash: 'bg-primary/10 text-primary',
        bank: 'bg-success/10 text-success',
        online: 'bg-primary/10 text-primary',
        jazzcash: 'bg-info/10 text-info',
        easypaisa: 'bg-info/10 text-info',
        cheque: 'bg-warning/10 text-warning',
    };
    return colors[method] || 'bg-muted text-foreground';
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
                <h1 class="text-xl md:text-2xl font-bold text-foreground">
                    Payment Methods Report
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Fee collection by payment method
                </p>
            </div>

            <!-- Filters -->
            <div class="bg-card rounded-lg border border-border p-4">
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
                <div class="bg-card rounded-lg border border-border p-4">
                    <p class="text-sm text-muted-foreground">Total Amount Collected</p>
                    <p class="text-3xl font-bold text-foreground">{{ formatCurrency(calculateTotal()) }}</p>
                </div>
                <div class="bg-card rounded-lg border border-border p-4">
                    <p class="text-sm text-muted-foreground">Total Transactions</p>
                    <p class="text-3xl font-bold text-foreground">{{ calculateCount() }}</p>
                </div>
            </div>

            <!-- Payment Methods Breakdown -->
            <div class="bg-card rounded-lg border border-border p-4">
                <h3 class="text-lg font-semibold text-foreground mb-4">By Payment Method</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="text-left py-3 px-4 text-muted-foreground">Payment Method</th>
                                <th class="text-right py-3 px-4 text-muted-foreground">Transactions</th>
                                <th class="text-right py-3 px-4 text-muted-foreground">Amount</th>
                                <th class="text-right py-3 px-4 text-muted-foreground">% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="method in props.byMethod" :key="method.payment_method" class="border-b border-border">
                                <td class="py-3 px-4">
                                    <span :class="['inline-flex items-center px-3 py-1 rounded-full text-sm font-medium', getPaymentMethodColor(method.payment_method)]">
                                        {{ getPaymentMethodLabel(method.payment_method) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right text-foreground">{{ method.count }}</td>
                                <td class="py-3 px-4 text-right text-foreground font-medium">{{ formatCurrency(method.total) }}</td>
                                <td class="py-3 px-4 text-right text-muted-foreground">
                                    {{ calculateTotal() > 0 ? ((method.total / calculateTotal()) * 100).toFixed(1) : 0 }}%
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-muted">
                                <td class="py-3 px-4 font-semibold text-foreground">Total</td>
                                <td class="py-3 px-4 text-right font-semibold text-foreground">{{ calculateCount() }}</td>
                                <td class="py-3 px-4 text-right font-bold text-foreground">{{ formatCurrency(calculateTotal()) }}</td>
                                <td class="py-3 px-4 text-right font-semibold text-foreground">100%</td>
                            </tr>
                        </tfoot>
                    </table>
                    <div v-if="props.byMethod.length === 0" class="text-center text-muted-foreground py-8">
                        No payment data available for the selected period
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
