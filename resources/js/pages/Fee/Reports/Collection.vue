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
                <h1 class="text-xl md:text-2xl font-bold text-foreground">
                    Collection Report
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Fee collection summary and breakdown
                </p>
            </div>

            <!-- Filters -->
            <div class="bg-card rounded-lg border border-border p-4">
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
                <div class="bg-card rounded-lg border border-border p-4">
                    <p class="text-sm text-muted-foreground">Total Received</p>
                    <p class="text-2xl font-bold text-foreground">{{ formatCurrency(props.summary.total_received) }}</p>
                </div>
                <div class="bg-card rounded-lg border border-border p-4">
                    <p class="text-sm text-muted-foreground">Total Allocated</p>
                    <p class="text-2xl font-bold text-primary">{{ formatCurrency(props.summary.total_allocated) }}</p>
                </div>
                <div class="bg-card rounded-lg border border-border p-4">
                    <p class="text-sm text-muted-foreground">Wallet/Credit</p>
                    <p class="text-2xl font-bold text-success">{{ formatCurrency(props.summary.total_wallet) }}</p>
                </div>
                <div class="bg-card rounded-lg border border-border p-4">
                    <p class="text-sm text-muted-foreground">Total Transactions</p>
                    <p class="text-2xl font-bold text-foreground">{{ props.summary.payment_count }}</p>
                </div>
            </div>

            <!-- Payment Methods Breakdown -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- By Payment Method -->
                <div class="bg-card rounded-lg border border-border p-4">
                    <h3 class="text-lg font-semibold text-foreground mb-4">By Payment Method</h3>
                    <div class="space-y-3">
                        <div v-for="method in props.byMethod" :key="method.payment_method" class="flex flex-wrap gap-2 justify-between items-center">
                            <span class="text-muted-foreground">{{ getPaymentMethodLabel(method.payment_method) }}</span>
                            <span class="font-medium text-foreground">{{ formatCurrency(method.total) }}</span>
                        </div>
                        <div v-if="props.byMethod.length === 0" class="text-center text-muted-foreground py-4">
                            No payments found
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="bg-card rounded-lg border border-border p-4">
                    <h3 class="text-lg font-semibold text-foreground mb-4">Summary</h3>
                    <div class="space-y-3">
                        <div class="flex flex-wrap gap-2 justify-between">
                            <span class="text-muted-foreground">Total Received</span>
                            <span class="font-medium text-foreground">{{ formatCurrency(props.summary.total_received) }}</span>
                        </div>
                        <div class="flex flex-wrap gap-2 justify-between">
                            <span class="text-muted-foreground">Allocated to Vouchers</span>
                            <span class="font-medium text-primary">{{ formatCurrency(props.summary.total_allocated) }}</span>
                        </div>
                        <div class="flex flex-wrap gap-2 justify-between">
                            <span class="text-muted-foreground">Added to Wallet</span>
                            <span class="font-medium text-success">{{ formatCurrency(props.summary.total_wallet) }}</span>
                        </div>
                        <div class="flex flex-wrap gap-2 justify-between pt-3 border-t border-border">
                            <span class="font-semibold text-foreground">Number of Payments</span>
                            <span class="font-bold text-foreground">{{ props.summary.payment_count }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Collection Chart -->
            <div class="bg-card rounded-lg border border-border p-4">
                <h3 class="text-lg font-semibold text-foreground mb-4">Daily Collection</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="text-left py-2 text-muted-foreground">Date</th>
                                <th class="text-right py-2 text-muted-foreground">Collection</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="day in props.dailyCollection" :key="day.date" class="border-b border-border">
                                <td class="py-2 text-foreground">{{ day.date }}</td>
                                <td class="py-2 text-right text-foreground font-medium">{{ formatCurrency(day.total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="props.dailyCollection.length === 0" class="text-center text-muted-foreground py-4">
                        No daily collection data available
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
